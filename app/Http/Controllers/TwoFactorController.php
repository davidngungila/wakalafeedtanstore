<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\TwoFactor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class TwoFactorController extends Controller
{
    public function show(Request $request): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        $userId = $request->session()->get('two_factor_user_id');

        if (! $userId) {
            return redirect()->route('login');
        }

        $email = $request->session()->get('two_factor_user_email');

        return view('auth.two-factor', ['email' => $email ?: $request->input('email', '')]);
    }

    public function verify(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => ['required', 'string', 'max:32'],
        ]);

        $userId = $request->session()->get('two_factor_user_id');

        if (! $userId) {
            return redirect()->route('login')->withErrors(['email' => 'Your sign-in session has expired. Please sign in again.']);
        }

        $user = User::find($userId);

        if (! $user || ! $user->two_factor_enabled || ! $user->two_factor_secret) {
            return redirect()->route('login')->withErrors(['email' => 'Your sign-in session has expired. Please sign in again.']);
        }

        $code = $request->string('code')->trim()->toString();

        // Also accept OTP sent via email (if enabled) — check cache otp_email_{user_id}
        $emailOtp = Cache::get('otp_email_'.$user->id);
        if ($emailOtp && hash_equals((string) $emailOtp, $code)) {
            Cache::forget('otp_email_'.$user->id);
            $request->session()->forget(['two_factor_user_id', 'two_factor_user_email']);
            Auth::login($user);

            return $this->completeLogin($request, false);
        }

        $recovered = false;

        if (! TwoFactor::verifyCode(Crypt::decryptString($user->two_factor_secret), $code)) {
            $codes = $user->two_factor_recovery_codes ?? [];
            $hash = TwoFactor::hashRecoveryCode($code);

            if (! in_array($hash, $codes, true)) {
                return back()->withErrors(['code' => 'The code is invalid or has expired.']);
            }

            $recovered = true;

            $user->forceFill([
                'two_factor_recovery_codes' => array_values(array_filter($codes, fn (string $candidate) => $candidate !== $hash)),
            ])->save();
        }

        $request->session()->forget(['two_factor_user_id', 'two_factor_user_email']);

        Auth::login($user);

        return $this->completeLogin($request, $recovered);
    }

    public function resend(Request $request): JsonResponse|RedirectResponse
    {
        $userId = $request->session()->get('two_factor_user_id');
        if (! $userId) {
            return $request->expectsJson() ? response()->json(['success' => false, 'message' => 'Session expired'], 422) : redirect()->route('login')->withErrors(['email' => 'Session expired']);
        }
        $user = User::find($userId);
        if (! $user) {
            return $request->expectsJson() ? response()->json(['success' => false, 'message' => 'User not found'], 422) : redirect()->route('login');
        }
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        Cache::put('otp_email_'.$user->id, $code, 300);
        try {
            Mail::raw('Your Wakala Feedtan Store OTP code is: '.$code."\n\nValid for 5 minutes.", function ($message) use ($user) {
                $message->to($user->email)->subject('Your OTP Code — Wakala — '.now()->format('H:i'));
            });
            $msg = 'OTP resent to '.$user->email.' — check inbox/spam';

            return $request->expectsJson() ? response()->json(['success' => true, 'message' => $msg]) : back()->with('status', $msg);
        } catch (\Throwable $e) {
            \Log::warning('OTP resend failed', ['error' => $e->getMessage()]);
            $msg = 'Failed to send OTP: '.$e->getMessage();

            return $request->expectsJson() ? response()->json(['success' => false, 'message' => $msg], 500) : back()->withErrors(['code' => $msg]);
        }
    }

    public function cancel(Request $request): RedirectResponse
    {
        $request->session()->forget(['two_factor_user_id', 'two_factor_user_email']);

        return redirect()->route('login');
    }
}
