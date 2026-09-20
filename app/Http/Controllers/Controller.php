<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\DailyOpening;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request as RequestFacade;

abstract class Controller
{
    /**
     * Persist an entry in the audit trail.
     *
     * @param  array<string, mixed>|null  $details
     */
    protected function recordAudit(
        string $action,
        ?string $entityType = null,
        ?int $entityId = null,
        ?array $details = null
    ): void {
        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'details' => $details,
            'ip_address' => RequestFacade::ip(),
        ]);
    }

    /**
     * Finish a successful sign-in. The caller must already have authenticated the user.
     */
    protected function completeLogin(Request $request, bool $recovery = false): JsonResponse|RedirectResponse
    {
        $user = Auth::user();

        $user->forceFill(['last_login_at' => now()])->save();

        $this->recordAudit(
            $recovery ? 'User logged in with a recovery code' : 'User logged in',
            'User',
            $user->id,
            ['email' => $user->email],
        );

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Welcome back, '.$user->name.'!']);
        }

        $request->session()->regenerate();

        if (! $user->two_factor_enabled) {
            return redirect()->intended(route('account.index'))
                ->with('status', 'Set up two-factor authentication before you continue.');
        }

        if (in_array($user->role, ['cashier', 'supervisor', 'admin'], true)) {
            $agent = cash_point();

            if ($agent !== null) {
                $todayOpening = DailyOpening::forAgentAndDate($agent->id, today())
                    ->open()
                    ->first();

                if ($todayOpening === null) {
                    return redirect()->intended(route('daily-opening.create'));
                }
            }
        }

        return redirect()->intended(route('dashboard'));
    }
}
