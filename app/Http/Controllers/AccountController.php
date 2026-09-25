<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\SmsSender;
use App\Support\SessionFormatter;
use App\Support\TwoFactor;
use App\Support\TwoFactorMethods;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function updateTwoFactorMethod(Request $request, SmsSender $sender): JsonResponse
    {
        $validated = $request->validate([
            'method' => ['required', 'in:app,sms'],
        ]);

        $user = auth()->user();

        if ($validated['method'] === 'sms') {
            $phone = $this->smsPhone($user, $sender);

            if ($phone === null) {
                return response()->json(['success' => false, 'message' => 'Verify your mobile number before using SMS OTP. A configured SMS provider is also required.'], 422);
            }
        }

        if ($validated['method'] === 'app' && ! $user->two_factor_app_enabled) {
            return response()->json(['success' => false, 'message' => 'Set up the authenticator app before selecting it as the default method.'], 422);
        }

        $user->forceFill(['two_factor_method' => $validated['method']])->save();

        if ($validated['method'] !== 'sms') {
            Cache::forget('otp_sms_'.$user->id);
        }

        $this->recordAudit('Two-factor method updated', 'User', $user->id, ['method' => $validated['method']]);

        return response()->json(['success' => true, 'message' => 'Verification method set to '.($validated['method'] === 'sms' ? 'SMS OTP' : 'Authenticator App').'.']);
    }

    public function enableSmsTwoFactor(Request $request, SmsSender $sender): JsonResponse
    {
        $user = auth()->user();
        $phone = $this->smsPhone($user, $sender);

        if ($phone === null) {
            return response()->json(['success' => false, 'message' => 'Verify your mobile number before using SMS OTP. A configured SMS provider is also required.'], 422);
        }

        if (! $user->two_factor_enabled) {
            // For SMS OTP we don't need TOTP secret — use a random secret but mark method as SMS.
            $secret = TwoFactor::generateSecret();
            $recoveryCodes = TwoFactor::generateRecoveryCodes();

            $user->forceFill([
                'two_factor_secret' => Crypt::encryptString($secret),
                'two_factor_enabled' => true,
                'two_factor_method' => 'sms',
                'two_factor_app_enabled' => false,
                'two_factor_recovery_codes' => array_map(fn (string $code): string => TwoFactor::hashRecoveryCode($code), $recoveryCodes),
            ])->save();

            Cache::forget('otp_sms_'.$user->id);
            $request->session()->forget('two_factor_pending_secret');

            $this->recordAudit('Two-factor enabled via SMS OTP', 'User', $user->id);

            return response()->json(['success' => true, 'message' => 'SMS OTP enabled. Codes will be sent to '.$phone.' at login.', 'recovery_codes' => $recoveryCodes]);
        }

        $user->forceFill(['two_factor_method' => 'sms'])->save();
        Cache::forget('otp_sms_'.$user->id);

        $this->recordAudit('SMS OTP added as the default two-factor method', 'User', $user->id);

        return response()->json(['success' => true, 'message' => 'SMS OTP enabled. Codes will be sent to '.$phone.' at login.']);
    }

    private function smsPhone(User $user, SmsSender $sender): ?string
    {
        return TwoFactorMethods::verifiedPhone($user, $sender);
    }

    public function index(Request $request): View
    {
        $user = auth()->user();

        $pendingSecret = $request->session()->get('two_factor_pending_secret');

        if ((! $user->two_factor_enabled || ! $user->two_factor_app_enabled) && ! $pendingSecret) {
            $pendingSecret = TwoFactor::generateSecret();
            $request->session()->put('two_factor_pending_secret', $pendingSecret);
        }

        $currentSessionId = $request->session()->getId();

        $parse = fn (object $row, bool $isCurrent): array => SessionFormatter::format($row, $isCurrent);

        $currentRow = DB::table('sessions')->where('id', $currentSessionId)->first();

        $sessions = DB::table('sessions')
            ->where('user_id', $user->id)
            ->where('id', '!=', $currentSessionId)
            ->orderByDesc('last_activity')
            ->limit(20)
            ->get()
            ->map(fn (object $row): array => $parse($row, false));

        return view('account.index', [
            'user' => $user,
            'sessions' => $sessions,
            'currentSession' => $currentRow ? $parse($currentRow, true) : null,
            'pendingSecret' => $pendingSecret,
            'currentSessionId' => $currentSessionId,
        ]);
    }

    public function updatePassword(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $user = auth()->user();

        if (! Hash::check($validated['current_password'], $user->password)) {
            return response()->json(['success' => false, 'message' => 'Your current password is incorrect.'], 422);
        }

        $user->update(['password' => Hash::make($validated['password'])]);

        $this->recordAudit('Password changed', 'User', $user->id);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Password changed successfully.']);
        }

        return back()->with('status', 'Password changed successfully.');
    }

    public function confirmTwoFactor(Request $request): JsonResponse
    {
        $user = auth()->user();

        if ($user->two_factor_enabled && $user->two_factor_app_enabled) {
            return response()->json(['success' => false, 'message' => 'Two-factor authentication is already enabled.'], 422);
        }

        $secret = $request->session()->get('two_factor_pending_secret');

        if (! $secret) {
            return response()->json(['success' => false, 'message' => 'Please reload the page and try again.'], 422);
        }

        $request->validate([
            'code' => ['required', 'string', 'max:16'],
        ]);

        if (! TwoFactor::verifyCode($secret, $request->string('code')->trim()->toString())) {
            return response()->json(['success' => false, 'message' => 'The code is invalid.'], 422);
        }

        $attributes = [
            'two_factor_secret' => Crypt::encryptString($secret),
            'two_factor_enabled' => true,
            'two_factor_app_enabled' => true,
            'two_factor_method' => $user->two_factor_method ?? 'app',
        ];

        if (! $user->two_factor_enabled || empty($user->two_factor_recovery_codes)) {
            $recoveryCodes = TwoFactor::generateRecoveryCodes();
            $attributes['two_factor_recovery_codes'] = array_map(
                fn (string $code): string => TwoFactor::hashRecoveryCode($code),
                $recoveryCodes,
            );
        }

        $user->forceFill($attributes)->save();
        $request->session()->forget('two_factor_pending_secret');

        $this->recordAudit(
            $user->wasChanged('two_factor_enabled') ? 'Two-factor authentication enabled' : 'Authenticator app added to two-factor authentication',
            'User',
            $user->id
        );

        $response = [
            'success' => true,
            'message' => 'Authenticator app verification is now available.',
        ];

        if (isset($recoveryCodes)) {
            $response['recovery_codes'] = $recoveryCodes;
        }

        return response()->json($response);
    }

    public function disableTwoFactor(Request $request): JsonResponse
    {
        $user = auth()->user();

        $request->validate([
            'current_password' => ['required', 'string'],
        ]);

        if (! Hash::check($request->string('current_password')->toString(), $user->password)) {
            return response()->json(['success' => false, 'message' => 'Your current password is incorrect.'], 422);
        }

        if (! $user->two_factor_enabled) {
            return response()->json(['success' => true, 'message' => 'Two-factor authentication was not enabled.']);
        }

        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_enabled' => false,
            'two_factor_app_enabled' => false,
            'two_factor_recovery_codes' => null,
        ])->save();

        Cache::forget('otp_sms_'.$user->id);

        $request->session()->forget('two_factor_pending_secret');

        $this->recordAudit('Two-factor authentication disabled', 'User', $user->id);

        return response()->json(['success' => true, 'message' => 'Two-factor authentication has been disabled.']);
    }

    public function refreshRecoveryCodes(Request $request): JsonResponse
    {
        $user = auth()->user();

        $request->validate([
            'current_password' => ['required', 'string'],
        ]);

        if (! Hash::check($request->string('current_password')->toString(), $user->password)) {
            return response()->json(['success' => false, 'message' => 'Your current password is incorrect.'], 422);
        }

        if (! $user->two_factor_enabled) {
            return response()->json(['success' => false, 'message' => 'Two-factor authentication is not enabled.'], 422);
        }

        $recoveryCodes = TwoFactor::generateRecoveryCodes();

        $user->forceFill([
            'two_factor_recovery_codes' => array_map(
                fn (string $code): string => TwoFactor::hashRecoveryCode($code),
                $recoveryCodes,
            ),
        ])->save();

        $this->recordAudit('Recovery codes regenerated', 'User', $user->id);

        return response()->json([
            'success' => true,
            'message' => 'New recovery codes have been generated.',
            'recovery_codes' => $recoveryCodes,
        ]);
    }

    public function revokeSession(Request $request, string $sessionId): JsonResponse
    {
        $user = auth()->user();

        if ($sessionId === $request->session()->getId()) {
            return response()->json(['success' => false, 'message' => 'You cannot revoke your current session.'], 422);
        }

        DB::table('sessions')
            ->where('id', $sessionId)
            ->where('user_id', $user->id)
            ->delete();

        $this->recordAudit('Active session revoked', 'User', $user->id, ['session' => $sessionId]);

        return response()->json(['success' => true, 'message' => 'Session revoked successfully.']);
    }
}
