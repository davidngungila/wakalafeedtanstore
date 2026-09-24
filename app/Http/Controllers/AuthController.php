<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLoginForm(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): JsonResponse|RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! $user->is_active) {
            return $this->loginFailed($request, 'This account is inactive or does not exist.');
        }

        if (! Hash::check($credentials['password'], $user->password)) {
            return $this->loginFailed($request, 'These credentials do not match our records.');
        }

        if (! $user->two_factor_enabled) {
            Auth::login($user, $request->boolean('remember'));

            return $this->completeLogin($request);
        }

        if (! $user->two_factor_secret) {
            return $this->loginFailed($request, 'Two-factor authentication is misconfigured. Contact support.');
        }

        $request->session()->put('two_factor_user_id', $user->id);
        $request->session()->put('two_factor_user_email', $user->email);
        $request->session()->regenerate();

        // If OTP via email is enabled in settings, generate and send email OTP (also allow TOTP)
        try {
            $emailSettings = Setting::where('key', 'email')->value('value');
            $otpViaEmail = is_array($emailSettings) && ($emailSettings['otp_via_email'] ?? '1') == '1' && ($emailSettings['otp_enabled'] ?? '1') == '1';
            if ($otpViaEmail) {
                $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                Cache::put('otp_email_'.$user->id, $code, 300);
                $to = $user->email;
                Mail::raw('Your Wakala Feedtan Store OTP code is: '.$code."\n\nValid for 5 minutes. If you did not request this, ignore.", function ($message) use ($to) {
                    $message->to($to)->subject('Your OTP Code — Wakala Feedtan Store — '.now()->format('H:i'));
                });
            }
        } catch (\Throwable $e) {
            \Log::warning('OTP email failed', ['error' => $e->getMessage(), 'user_id' => $user->id]);
        }

        $this->recordAudit('Two-factor challenge started', 'User', $user->id);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'two_factor' => true,
                'message' => 'Enter your two-factor authentication code.',
                'redirect' => route('two-factor.show'),
            ]);
        }

        return redirect()->route('two-factor.show');
    }

    public function logout(Request $request): RedirectResponse
    {
        $this->recordAudit('User logged out', 'User', Auth::id());

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function loginFailed(Request $request, string $message): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['success' => false, 'message' => $message], 422);
        }

        return back()->withErrors(['email' => $message])->withInput($request->only('email'));
    }
}
