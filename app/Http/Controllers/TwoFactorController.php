<?php

namespace App\Http\Controllers;

use App\Mail\OtpMail;
use App\Models\Setting;
use App\Models\User;
use App\Support\TwoFactor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Throwable;

class TwoFactorController extends Controller
{
    public function show(Request $request): JsonResponse|View|RedirectResponse
    {
        $user = $this->challengedUser($request);

        if (! $user) {
            return $this->expiredChallenge($request);
        }

        return view('auth.two-factor', [
            'email' => $user->email,
            'method' => $user->two_factor_method === 'email' ? 'email' : 'app',
        ]);
    }

    public function verify(Request $request): JsonResponse|RedirectResponse
    {
        $request->validate([
            'code' => ['required', 'string', 'max:32'],
        ]);

        $user = $this->challengedUser($request);

        if (! $user) {
            return $this->expiredChallenge($request);
        }

        $code = $request->string('code')->trim()->toString();
        $verified = false;

        if (($user->two_factor_method ?? 'app') === 'email') {
            $emailOtp = Cache::get('otp_email_'.$user->id);
            $verified = is_string($emailOtp) && hash_equals($emailOtp, $code);
        } else {
            try {
                $verified = TwoFactor::verifyCode(Crypt::decryptString($user->two_factor_secret), $code);
            } catch (Throwable $exception) {
                Log::warning('Two-factor secret could not be decrypted', [
                    'error' => $exception->getMessage(),
                    'user_id' => $user->id,
                ]);

                return $this->expiredChallenge($request);
            }
        }

        $recovered = false;

        if (! $verified) {
            $recoveryCodes = $user->two_factor_recovery_codes ?? [];
            $hash = TwoFactor::hashRecoveryCode($code);

            if (! in_array($hash, $recoveryCodes, true)) {
                return back()->withErrors(['code' => 'The code is invalid or has expired.']);
            }

            $recovered = true;

            $user->forceFill([
                'two_factor_recovery_codes' => array_values(array_filter($recoveryCodes, fn (string $candidate) => $candidate !== $hash)),
            ])->save();
        }

        if (($user->two_factor_method ?? 'app') === 'email') {
            Cache::forget('otp_email_'.$user->id);
        }

        $request->session()->forget(['two_factor_user_id', 'two_factor_user_email']);

        Auth::login($user);

        return $this->completeLogin($request, $recovered);
    }

    public function resend(Request $request): JsonResponse|RedirectResponse
    {
        $user = $this->challengedUser($request);

        if (! $user) {
            return $this->expiredChallenge($request);
        }

        if (($user->two_factor_method ?? 'app') !== 'email') {
            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => 'Email OTP is not selected.'], 422)
                : back()->withErrors(['code' => 'Email OTP is not selected.']);
        }

        $emailSettings = Setting::where('key', 'email')->value('value');
        $emailOtpEnabled = is_array($emailSettings) && ($emailSettings['otp_via_email'] ?? '0') == '1' && ($emailSettings['otp_enabled'] ?? '0') == '1';

        if (! $emailOtpEnabled) {
            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => 'Email OTP is currently unavailable.'], 422)
                : back()->withErrors(['code' => 'Email OTP is currently unavailable.']);
        }

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        try {
            Mail::send(new OtpMail($code, $user->email, 5));
            Cache::put('otp_email_'.$user->id, $code, 300);
            $message = 'A new code was sent to '.$user->email.'.';

            return $request->expectsJson()
                ? response()->json(['success' => true, 'message' => $message])
                : back()->with('status', $message);
        } catch (Throwable $exception) {
            Log::warning('OTP resend failed', [
                'error' => $exception->getMessage(),
                'user_id' => $user->id,
            ]);
            $message = 'Could not send the code. Please try again.';

            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => $message], 500)
                : back()->withErrors(['code' => $message]);
        }
    }

    public function cancel(Request $request): RedirectResponse
    {
        $request->session()->forget(['two_factor_user_id', 'two_factor_user_email']);

        return redirect()->route('login');
    }

    private function challengedUser(Request $request): ?User
    {
        $userId = $request->session()->get('two_factor_user_id');

        if (! is_numeric($userId)) {
            return null;
        }

        $user = User::find((int) $userId);

        if (! $user || ! $user->is_active || ! $user->two_factor_enabled || ! $user->two_factor_secret) {
            return null;
        }

        return $user;
    }

    private function expiredChallenge(Request $request): JsonResponse|RedirectResponse
    {
        $request->session()->forget(['two_factor_user_id', 'two_factor_user_email']);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'Your sign-in session has expired. Please sign in again.',
            ], 422);
        }

        return redirect()->route('login')
            ->withErrors(['email' => 'Your sign-in session has expired. Please sign in again.']);
    }
}
