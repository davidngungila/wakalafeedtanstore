<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTwoFactorEnabled
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->two_factor_enabled) {
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
            'account.two-factor.enable-email',
            'account.recovery-codes',
            'account.sessions.destroy',
            'profile.index',
            'profile.update',
            'profile.password',
            'avatar.show',
        ];

        if (in_array($routeName, $allowedRoutes, true)) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'Set up two-factor authentication before proceeding.',
                'redirect' => route('account.index'),
                'two_factor_required' => true,
            ], 422);
        }

        return redirect()->route('account.index')
            ->with('status', 'Set up two-factor authentication before you continue.');
    }
}
