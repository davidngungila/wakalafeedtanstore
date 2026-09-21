<?php

namespace App\Http\Middleware;

use App\Models\DailyOpening;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureDailyOpeningSet
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->role !== 'cashier') {
            return $next($request);
        }

        $agent = cash_point();

        if ($agent === null) {
            return $next($request);
        }

        $routeName = $request->route()?->getName() ?? '';

        $allowedRoutes = [
            'daily-opening.create',
            'daily-opening.store',
            'daily-opening.show',
            'daily-opening.index',
            'daily-opening.close',
            'logout',
            'profile.index',
            'profile.update',
            'profile.password',
            'account.index',
            'account.password',
            'account.two-factor.confirm',
            'account.two-factor.disable',
            'account.recovery-codes',
            'account.sessions.destroy',
            'avatar.show',
            'devices.index',
            'devices.register',
            'devices.store',
            'devices.show',
            'devices.update',
            'devices.destroy',
            'devices.approve',
            'devices.block',
            'devices.code',
            'devices.connect-status',
            'devices.lines.store',
            'devices.lines.destroy',
            'devices.phones.status',
            'devices.revoke',
            'devices.suspend',
            'sms.index',
            'sms.stream',
            'finance.index',
            'finance.accounts.index',
            'finance.accounts.store',
            'finance.accounts.update',
            'finance.accounts.destroy',
            'finance.ledger.index',
            'finance.journals.index',
            'finance.journals.store',
            'finance.journals.post',
            'finance.journals.reverse',
            'finance.statements.balance',
            'finance.statements.income',
            'networks.index',
            'networks.store',
            'networks.show',
            'networks.update',
            'networks.destroy',
            'networks.updateRates',
        ];

        if (in_array($routeName, $allowedRoutes, true)) {
            return $next($request);
        }

        $todayOpening = DailyOpening::forAgentAndDate($agent->id, today())
            ->open()
            ->first();

        if ($todayOpening === null) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please enter today\'s opening balances before proceeding.',
                    'redirect' => route('daily-opening.create'),
                    'daily_opening_required' => true,
                ], 422);
            }

            return redirect()->route('daily-opening.create')
                ->with('status', 'Please enter today\'s opening balances (cash in hand and float) before continuing.');
        }

        return $next($request);
    }
}
