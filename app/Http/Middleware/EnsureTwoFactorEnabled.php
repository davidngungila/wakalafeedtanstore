<?php

namespace App\Http\Middleware;

use App\Services\SmsSender;
use App\Support\TwoFactorMethods;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTwoFactorEnabled
{
    public function __construct(private SmsSender $sender) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        $issue = TwoFactorMethods::setupIssue($user, $this->sender);

        if ($issue === null) {
            return $next($request);
        }

        $routeName = $request->route()?->getName() ?? '';

        $allowedRoutes = [
            'logout',
            'account.index',
            'account.password',
            'account.two-factor.confirm',
            'account.two-factor.disable',
            'account.two-factor.method',
            'account.two-factor.enable-sms',
            'account.phone.verification.store',
            'account.phone.verify',
            'account.recovery-codes',
            'account.sessions.destroy',
            'profile.index',
            'profile.edit',
            'profile.update',
            'profile.password',
            'avatar.show',
        ];

        if (in_array($routeName, $allowedRoutes, true)) {
            return $next($request);
        }

        $message = $issue === 'misconfigured'
            ? 'Your two-factor authentication is misconfigured. Set it up again before proceeding.'
            : 'Set up two-factor authentication before proceeding.';

        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => $message,
                'redirect' => route('account.index'),
                'two_factor_required' => true,
                'two_factor_issue' => $issue,
            ], 422);
        }

        return redirect()->route('account.index')
            ->with('two_factor_alert', ['issue' => $issue, 'message' => $message]);
    }
}
