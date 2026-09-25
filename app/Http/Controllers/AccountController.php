<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Support\TwoFactor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function updateTwoFactorMethod(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'method' => ['required', 'in:app,email'],
        ]);

        $user = auth()->user();

        // Email OTP requires email settings enabled
        if ($validated['method'] === 'email') {
            $emailSettings = Setting::where('key', 'email')->value('value');
            $otpViaEmail = is_array($emailSettings) && ($emailSettings['otp_via_email'] ?? '0') == '1' && ($emailSettings['otp_enabled'] ?? '0') == '1';
            if (! $otpViaEmail) {
                return response()->json(['success' => false, 'message' => 'Email OTP is not enabled in system settings.'], 422);
            }
        }

        $user->forceFill(['two_factor_method' => $validated['method']])->save();

        if ($validated['method'] !== 'email') {
            Cache::forget('otp_email_'.$user->id);
        }

        $this->recordAudit('Two-factor method updated', 'User', $user->id, ['method' => $validated['method']]);

        return response()->json(['success' => true, 'message' => 'Verification method set to '.($validated['method'] === 'email' ? 'Email OTP' : 'Authenticator App').'.']);
    }

    public function enableEmailTwoFactor(Request $request): JsonResponse
    {
        $user = auth()->user();

        if ($user->two_factor_enabled) {
            return response()->json(['success' => false, 'message' => 'Two-factor is already enabled. Disable first to switch method.'], 422);
        }

        $emailSettings = Setting::where('key', 'email')->value('value');
        $otpViaEmail = is_array($emailSettings) && ($emailSettings['otp_via_email'] ?? '0') == '1' && ($emailSettings['otp_enabled'] ?? '0') == '1';
        if (! $otpViaEmail) {
            return response()->json(['success' => false, 'message' => 'Email OTP is not enabled in system settings.'], 422);
        }

        // For Email OTP we don't need TOTP secret — use a random secret but mark method as email
        $secret = TwoFactor::generateSecret();

        $recoveryCodes = TwoFactor::generateRecoveryCodes();

        $user->forceFill([
            'two_factor_secret' => Crypt::encryptString($secret),
            'two_factor_enabled' => true,
            'two_factor_method' => 'email',
            'two_factor_recovery_codes' => array_map(fn (string $code): string => TwoFactor::hashRecoveryCode($code), $recoveryCodes),
        ])->save();

        Cache::forget('otp_email_'.$user->id);
        $request->session()->forget('two_factor_pending_secret');

        $this->recordAudit('Two-factor enabled via Email OTP', 'User', $user->id);

        return response()->json(['success' => true, 'message' => 'Email OTP enabled. Codes will be sent to '.$user->email.' at login.', 'recovery_codes' => $recoveryCodes]);
    }

    public function index(Request $request): View
    {
        $user = auth()->user();

        $pendingSecret = $request->session()->get('two_factor_pending_secret');

        if (! $user->two_factor_enabled && ! $pendingSecret) {
            $pendingSecret = TwoFactor::generateSecret();
            $request->session()->put('two_factor_pending_secret', $pendingSecret);
        }

        $currentSessionId = $request->session()->getId();

        $parse = fn (object $row, bool $isCurrent): array => [
            'id' => $row->id,
            'ip' => $row->ip_address ?: 'Unknown',
            'device' => $this->parseDevice($row->user_agent),
            'browser' => $this->parseBrowser($row->user_agent),
            'ua' => $row->user_agent,
            'last_seen' => Carbon::createFromTimestamp($row->last_activity),
            'is_current' => $isCurrent,
        ];

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

        if ($user->two_factor_enabled) {
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

        $recoveryCodes = TwoFactor::generateRecoveryCodes();

        $user->forceFill([
            'two_factor_secret' => Crypt::encryptString($secret),
            'two_factor_enabled' => true,
            'two_factor_method' => 'app',
            'two_factor_recovery_codes' => array_map(
                fn (string $code): string => TwoFactor::hashRecoveryCode($code),
                $recoveryCodes,
            ),
        ])->save();

        $request->session()->forget('two_factor_pending_secret');

        $this->recordAudit('Two-factor authentication enabled', 'User', $user->id);

        return response()->json([
            'success' => true,
            'message' => 'Two-factor authentication is now enabled.',
            'recovery_codes' => $recoveryCodes,
        ]);
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
            'two_factor_recovery_codes' => null,
        ])->save();

        Cache::forget('otp_email_'.$user->id);

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

    private function parseDevice(?string $userAgent): string
    {
        if (! $userAgent) {
            return 'Unknown device';
        }

        if (preg_match('/Windows/', $userAgent)) {
            return 'Windows';
        }

        if (preg_match('/Macintosh|Mac OS X/', $userAgent)) {
            return 'macOS';
        }

        if (preg_match('/iPhone/', $userAgent)) {
            return 'iPhone';
        }

        if (preg_match('/iPad/', $userAgent)) {
            return 'iPad';
        }

        if (preg_match('/Android/', $userAgent)) {
            return 'Android';
        }

        if (preg_match('/Linux/', $userAgent)) {
            return 'Linux';
        }

        return 'Browser';
    }

    private function parseBrowser(?string $userAgent): string
    {
        if (! $userAgent) {
            return 'Unknown browser';
        }

        if (preg_match('/Edg\//', $userAgent)) {
            return 'Microsoft Edge';
        }

        if (preg_match('/OPR\//', $userAgent)) {
            return 'Opera';
        }

        if (preg_match('/SamsungBrowser/', $userAgent)) {
            return 'Samsung Internet';
        }

        if (preg_match('/CriOS\//', $userAgent)) {
            return 'Chrome (iOS)';
        }

        if (preg_match('/Chrome\//', $userAgent)) {
            return 'Chrome';
        }

        if (preg_match('/Firefox\/|FxiOS\//', $userAgent)) {
            return 'Firefox';
        }

        if (preg_match('/Safari\//', $userAgent) && ! preg_match('/Android/', $userAgent)) {
            return 'Safari';
        }

        return 'Browser';
    }
}
