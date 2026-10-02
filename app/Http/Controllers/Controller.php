<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\DailyOpening;
use App\Services\SmsSender;
use App\Support\Shift;
use App\Support\TwoFactorMethods;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
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
     *
     * A user whose two-factor authentication is misconfigured is signed in but
     * held on the account page until they set up a working method again.
     */
    protected function completeLogin(Request $request, SmsSender $sender, bool $recovery = false): JsonResponse|RedirectResponse
    {
        $user = Auth::user();

        $setupIssue = TwoFactorMethods::setupIssue($user, $sender);

        $user->forceFill(['last_login_at' => now()])->save();

        $this->recordAudit(match (true) {
            $recovery => 'User logged in with a recovery code',
            $setupIssue === 'misconfigured' => 'User logged in with two-factor authentication misconfigured',
            default => 'User logged in',
        }, 'User', $user->id, ['email' => $user->email]);

        if ($request->expectsJson()) {
            return response()->json(array_filter([
                'success' => true,
                'message' => 'Welcome back, '.$user->name.'!',
                'two_factor_setup_required' => $setupIssue !== null ? true : null,
                'two_factor_issue' => $setupIssue,
                'redirect' => $setupIssue !== null ? route('account.index') : null,
            ]));
        }

        $request->session()->regenerate();

        if ($setupIssue !== null) {
            return redirect()->intended(route('account.index'))
                ->with('two_factor_alert', [
                    'issue' => $setupIssue,
                    'message' => $setupIssue === 'misconfigured'
                        ? 'Your two-factor authentication was misconfigured, so no verification code could be sent. Set it up again to protect your account.'
                        : 'Set up two-factor authentication before you continue.',
                ]);
        }

        if (in_array($user->role, ['cashier', 'supervisor', 'admin'], true)) {
            $agent = cash_point();

            if ($agent !== null) {
                $current = Shift::current();

                $todayOpening = DailyOpening::forAgentAndDate($agent->id, Carbon::parse($current['date']))
                    ->whereIn('shift', [$current['shift'], Shift::FULL])
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
