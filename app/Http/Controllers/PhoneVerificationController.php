<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\SmsSender;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use InvalidArgumentException;
use Throwable;

class PhoneVerificationController extends Controller
{
    public function store(Request $request, SmsSender $sender): JsonResponse
    {
        $user = $request->user();

        if ($user->phone_verified_at !== null) {
            return response()->json(['success' => true, 'message' => 'This mobile number is already verified.']);
        }

        if (! $sender->isConfigured()) {
            return response()->json(['success' => false, 'message' => 'Phone verification is currently unavailable. Ask an administrator to configure the SMS provider.'], 422);
        }

        try {
            $phone = $sender->normalizeRecipient((string) $user->phone);
        } catch (InvalidArgumentException) {
            return response()->json(['success' => false, 'message' => 'Add a valid mobile number to your profile before verification.'], 422);
        }

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        try {
            $sender->sendVerificationCode($phone, $code);
            Cache::put('phone_verification_'.$user->id, ['code' => $code, 'phone' => $phone], 300);
        } catch (Throwable $exception) {
            Log::warning('Phone verification code failed', ['error' => $exception->getMessage(), 'user_id' => $user->id]);

            return response()->json(['success' => false, 'message' => 'Could not send the verification code. Please try again.'], 500);
        }

        return response()->json(['success' => true, 'message' => 'A verification code was sent to '.$phone.'.']);
    }

    public function verify(Request $request, SmsSender $sender): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'size:6'],
        ]);

        $user = $request->user();

        if ($user->phone_verified_at !== null) {
            return response()->json(['success' => true, 'message' => 'This mobile number is already verified.']);
        }

        $stored = Cache::get('phone_verification_'.$user->id);

        if (! is_array($stored) || ! isset($stored['code'], $stored['phone'])) {
            return response()->json(['success' => false, 'message' => 'The verification code is invalid or has expired.'], 422);
        }

        try {
            $phone = $sender->normalizeRecipient((string) $user->phone);
        } catch (InvalidArgumentException) {
            return response()->json(['success' => false, 'message' => 'Add a valid mobile number to your profile before verification.'], 422);
        }

        if (! hash_equals((string) $stored['phone'], $phone) || ! hash_equals((string) $stored['code'], $validated['code'])) {
            return response()->json(['success' => false, 'message' => 'The verification code is invalid or has expired.'], 422);
        }

        $user->forceFill(['phone_verified_at' => now()])->save();
        Cache::forget('phone_verification_'.$user->id);

        $this->recordAudit('Phone number verified', 'User', $user->id);

        return response()->json(['success' => true, 'message' => 'Mobile number verified successfully.']);
    }

    public function loginShow(Request $request): RedirectResponse|View
    {
        $user = $this->challengedUser($request);

        if ($user === null) {
            return redirect()->route('login');
        }

        if ($user->phone_verified_at !== null) {
            return redirect()->route('two-factor.show');
        }

        return view('auth.phone-verification', ['phone' => mask_phone($user->phone)]);
    }

    public function loginVerify(Request $request, SmsSender $sender): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'size:6'],
        ]);

        $user = $this->challengedUser($request);

        if ($user === null) {
            return redirect()->route('login');
        }

        $stored = Cache::get('phone_verification_'.$user->id);

        if (! is_array($stored) || ! isset($stored['code'], $stored['phone'])) {
            return $this->verificationFailed($request, 'The verification code is invalid or has expired.');
        }

        try {
            $phone = $sender->normalizeRecipient((string) $user->phone);
        } catch (InvalidArgumentException) {
            return $this->verificationFailed($request, 'Add a valid mobile number to your profile before verification.');
        }

        if (! hash_equals((string) $stored['phone'], $phone) || ! hash_equals((string) $stored['code'], $validated['code'])) {
            return $this->verificationFailed($request, 'The verification code is invalid or has expired.');
        }

        $user->forceFill(['phone_verified_at' => now()])->save();
        Cache::forget('phone_verification_'.$user->id);

        $this->recordAudit('Phone number verified during login', 'User', $user->id);

        // Phone verified — continue with the SMS two-factor challenge.
        try {
            $loginCode = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $sender->sendLoginCode($phone, $loginCode);
            Cache::put('otp_sms_'.$user->id, $loginCode, 300);
        } catch (Throwable $exception) {
            Log::warning('SMS login code failed after phone verification', ['error' => $exception->getMessage(), 'user_id' => $user->id]);

            return $this->verificationFailed($request, 'Phone verified, but the login code could not be sent. Try signing in again.');
        }

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Phone verified. Enter the login code sent to your phone.', 'redirect' => route('two-factor.show')]);
        }

        return redirect()->route('two-factor.show');
    }

    public function loginResend(Request $request, SmsSender $sender): JsonResponse|RedirectResponse
    {
        $user = $this->challengedUser($request);

        if ($user === null) {
            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => 'Session expired. Please sign in again.'], 422)
                : redirect()->route('login');
        }

        if (! $sender->isConfigured()) {
            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => 'Phone verification is currently unavailable.'], 422)
                : back()->withErrors(['code' => 'Phone verification is currently unavailable.']);
        }

        try {
            $phone = $sender->normalizeRecipient((string) $user->phone);
            $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $sender->sendVerificationCode($phone, $code);
            Cache::put('phone_verification_'.$user->id, ['code' => $code, 'phone' => $phone], 300);
        } catch (Throwable $exception) {
            Log::warning('Phone verification resend failed', ['error' => $exception->getMessage(), 'user_id' => $user->id]);

            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => 'Could not send the verification code. Please try again.'], 500)
                : back()->withErrors(['code' => 'Could not send the verification code. Please try again.']);
        }

        return $request->expectsJson()
            ? response()->json(['success' => true, 'message' => 'A new verification code was sent.'])
            : back()->with('status', 'A new verification code was sent.');
    }

    private function challengedUser(Request $request): ?User
    {
        $id = $request->session()->get('two_factor_user_id');

        return $id ? User::find($id) : null;
    }

    private function verificationFailed(Request $request, string $message): JsonResponse|RedirectResponse
    {
        return $request->expectsJson()
            ? response()->json(['success' => false, 'message' => $message], 422)
            : back()->withErrors(['code' => $message]);
    }
}
