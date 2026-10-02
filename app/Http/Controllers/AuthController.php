<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\SmsSender;
use App\Support\TwoFactorMethods;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class AuthController extends Controller
{
    public function showLoginForm(): View
    {
        return view('auth.login');
    }

    public function login(Request $request, SmsSender $sender): JsonResponse|RedirectResponse
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

            return $this->completeLogin($request, $sender);
        }

        if (($user->two_factor_method ?? null) === 'sms') {
            if ($user->phone_verified_at === null) {
                if (! $sender->isConfigured()) {
                    return $this->loginFailed($request, 'SMS login codes are unavailable. Ask an administrator to configure the SMS provider.');
                }

                try {
                    $phone = $sender->normalizeRecipient((string) $user->phone);
                } catch (InvalidArgumentException) {
                    return $this->loginFailed($request, 'SMS login codes are unavailable. Ask an administrator to update your mobile number.');
                }

                $request->session()->put('two_factor_user_id', $user->id);
                $request->session()->put('two_factor_user_email', $user->email);
                $request->session()->regenerate();

                try {
                    $verificationCode = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                    $sender->sendVerificationCode($phone, $verificationCode);
                    Cache::put('phone_verification_'.$user->id, ['code' => $verificationCode, 'phone' => $phone], 300);
                } catch (Throwable $exception) {
                    Log::warning('Phone verification code failed during login', ['error' => $exception->getMessage(), 'user_id' => $user->id]);

                    return $this->loginFailed($request, 'Could not send the verification code. Please try again.');
                }

                $this->recordAudit('Phone verification started during login', 'User', $user->id);

                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => true,
                        'phone_verification' => true,
                        'message' => 'Enter the verification code sent to your phone.',
                        'redirect' => route('phone-verification.show'),
                    ]);
                }

                return redirect()->route('phone-verification.show');
            }

            if (! $sender->isConfigured()) {
                return $this->loginFailed($request, 'SMS login codes are unavailable. Ask an administrator to configure the SMS provider.');
            }

            try {
                $phone = $sender->normalizeRecipient((string) $user->phone);
            } catch (InvalidArgumentException) {
                return $this->loginFailed($request, 'SMS login codes are unavailable. Ask an administrator to update your mobile number.');
            }

            $failure = $this->sendSmsChallenge($request, $sender, $user, $phone);

            if ($failure !== null) {
                return $failure;
            }
        } else {
            $methods = TwoFactorMethods::available($user, $sender);
            $defaultMethod = TwoFactorMethods::default($methods, $user->two_factor_method);

            if ($defaultMethod === null) {
                return $this->loginWithMisconfiguredTwoFactor($request, $sender, $user);
            }

            if ($defaultMethod === 'sms') {
                $phone = TwoFactorMethods::verifiedPhone($user, $sender);

                if ($phone === null) {
                    return $this->loginWithMisconfiguredTwoFactor($request, $sender, $user);
                }

                $failure = $this->sendSmsChallenge($request, $sender, $user, $phone);

                if ($failure !== null) {
                    return $failure;
                }
            }
        }

        $request->session()->put('two_factor_user_id', $user->id);
        $request->session()->put('two_factor_user_email', $user->email);
        $request->session()->regenerate();

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
        $blockers = cashier_shift_blockers();

        if ($blockers !== []) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Complete the pending shift tasks before logging out.',
                    'blockers' => $blockers,
                ], 422);
            }

            return back()->with('error', 'Complete the pending shift tasks before logging out: '.implode(' ', $blockers));
        }

        $this->recordAudit('User logged out', 'User', Auth::id());

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function sendSmsChallenge(Request $request, SmsSender $sender, User $user, string $phone): JsonResponse|RedirectResponse|null
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        try {
            $sender->sendLoginCode($phone, $code);
            Cache::put('otp_sms_'.$user->id, $code, 300);
        } catch (RuntimeException $exception) {
            Log::warning('SMS login code failed', ['error' => $exception->getMessage(), 'user_id' => $user->id]);

            return $this->loginFailed($request, 'SMS login codes are unavailable. Ask an administrator to configure the SMS provider.');
        } catch (Throwable $exception) {
            Log::warning('SMS login code failed', ['error' => $exception->getMessage(), 'user_id' => $user->id]);

            return $this->loginFailed($request, 'Could not send the SMS login code. Please try again.');
        }

        return null;
    }

    /**
     * Sign the user in when two-factor authentication is enabled but no method can
     * deliver a code. The account page holds them until they set one up again.
     */
    private function loginWithMisconfiguredTwoFactor(Request $request, SmsSender $sender, User $user): JsonResponse|RedirectResponse
    {
        Log::warning('Two-factor authentication misconfigured at login', ['user_id' => $user->id]);

        Auth::login($user, $request->boolean('remember'));

        return $this->completeLogin($request, $sender);
    }

    private function loginFailed(Request $request, string $message): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['success' => false, 'message' => $message], 422);
        }

        return back()->withErrors(['email' => $message])->withInput($request->only('email'));
    }
}
