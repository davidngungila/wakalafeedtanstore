<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\SmsSender;
use App\Support\TwoFactor;
use App\Support\TwoFactorMethods;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

class TwoFactorController extends Controller
{
    public function show(Request $request, SmsSender $sender): JsonResponse|View|RedirectResponse
    {
        $user = $this->challengedUser($request);

        if (! $user) {
            return $this->expiredChallenge($request);
        }

        $methods = TwoFactorMethods::available($user, $sender);
        $method = TwoFactorMethods::default($methods, $user->two_factor_method);

        if ($method === null) {
            return $this->misconfiguredChallenge($request);
        }

        return view('auth.two-factor', [
            'phone' => $user->phone,
            'methods' => $methods,
            'method' => $method,
        ]);
    }

    public function verify(Request $request, SmsSender $sender): JsonResponse|RedirectResponse
    {
        $request->validate([
            'code' => ['required', 'string', 'max:32'],
            'method' => ['nullable', 'in:app,sms'],
        ]);

        $user = $this->challengedUser($request);

        if (! $user) {
            return $this->expiredChallenge($request);
        }

        $methods = TwoFactorMethods::available($user, $sender);
        $method = $request->input('method');

        if (! in_array($method, $methods, true)) {
            $method = TwoFactorMethods::default($methods, $user->two_factor_method);
        }

        if ($method === null) {
            return $this->misconfiguredChallenge($request);
        }

        $code = $request->string('code')->trim()->toString();
        $verified = false;

        if ($method === 'sms') {
            $smsOtp = Cache::get('otp_sms_'.$user->id);
            $verified = is_string($smsOtp) && hash_equals($smsOtp, $code);
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

        if ($method === 'sms') {
            Cache::forget('otp_sms_'.$user->id);
        }

        $request->session()->forget(['two_factor_user_id', 'two_factor_user_email']);

        Auth::login($user);

        return $this->completeLogin($request, $recovered);
    }

    public function resend(Request $request, SmsSender $sender): JsonResponse|RedirectResponse
    {
        $user = $this->challengedUser($request);

        if (! $user) {
            return $this->expiredChallenge($request);
        }

        if (! in_array('sms', TwoFactorMethods::available($user, $sender), true)) {
            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => 'SMS OTP is currently unavailable.'], 422)
                : back()->withErrors(['code' => 'SMS OTP is currently unavailable.']);
        }

        $phone = TwoFactorMethods::verifiedPhone($user, $sender);

        if ($phone === null) {
            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => 'SMS OTP is currently unavailable.'], 422)
                : back()->withErrors(['code' => 'SMS OTP is currently unavailable.']);
        }

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        try {
            $sender->sendLoginCode($phone, $code);
            Cache::put('otp_sms_'.$user->id, $code, 300);
            $message = 'A new code was sent to '.$phone.'.';

            return $request->expectsJson()
                ? response()->json(['success' => true, 'message' => $message])
                : back()->with('status', $message);
        } catch (RuntimeException $exception) {
            Log::warning('SMS OTP resend failed', [
                'error' => $exception->getMessage(),
                'user_id' => $user->id,
            ]);

            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => 'SMS OTP is currently unavailable.'], 422)
                : back()->withErrors(['code' => 'SMS OTP is currently unavailable.']);
        } catch (Throwable $exception) {
            Log::warning('SMS OTP resend failed', [
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

    private function misconfiguredChallenge(Request $request): JsonResponse|RedirectResponse
    {
        $request->session()->forget(['two_factor_user_id', 'two_factor_user_email']);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'Two-factor authentication is misconfigured. Contact support.',
            ], 422);
        }

        return redirect()->route('login')
            ->withErrors(['email' => 'Two-factor authentication is misconfigured. Contact support.']);
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
