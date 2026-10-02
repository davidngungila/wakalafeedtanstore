<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Keeps paginated URLs clean: a first-page visit is served from the plain
 * path (e.g. /audit) instead of bouncing through "?page=1", so the address
 * bar and any shared link never carry a redundant page parameter.
 */
class NormalizePageQuery
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->isMethod('GET') || $request->expectsJson()) {
            return $next($request);
        }

        $page = $request->query('page');

        if ($page === null || (string) $page !== '1') {
            return $next($request);
        }

        $query = $request->query();
        unset($query['page']);

        $url = $request->path() === '/' ? '/' : '/'.$request->path();

        if ($query !== []) {
            $url .= '?'.http_build_query($query);
        }

        return redirect()->to($url, 301);
    }
}
