<?php

namespace App\Http\Controllers;

use App\Services\SmsSender;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
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
}
