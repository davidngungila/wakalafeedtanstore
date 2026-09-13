<?php

namespace App\Http\Controllers;

use App\Support\TwoFactor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function index(Request $request): View
    {
        $user = auth()->user();

        $pendingSecret = $request->session()->get('two_factor_pending_secret');

        if (! $user->two_factor_enabled && ! $pendingSecret) {
            $pendingSecret = TwoFactor::generateSecret();
            $request->session()->put('two_factor_pending_secret', $pendingSecret);
        }

        $currentSessionId = $request->session()->getId();

        $sessions = DB::table('sessions')
            ->where('user_id', $user->id)
            ->where('id', '!=', $currentSessionId)
            ->orderByDesc('last_activity')
            ->limit(20)
            ->get()
            ->map(fn (object $row): array => [
                'id' => $row->id,
                'ip' => $row->ip_address ?: 'Unknown',
                'device' => $this->parseDevice($row->user_agent),
                'last_seen' => Carbon::createFromTimestamp($row->last_activity),
            ]);

        return view('account.index', [
            'user' => $user,
            'sessions' => $sessions,
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

        if (preg_match('/Linux/', $userAgent)) {
            return 'Linux';
        }

        if (preg_match('/iPhone|iPad/', $userAgent)) {
            return 'iOS device';
        }

        if (preg_match('/Android/', $userAgent)) {
            return 'Android device';
        }

        return 'Browser';
    }
}
