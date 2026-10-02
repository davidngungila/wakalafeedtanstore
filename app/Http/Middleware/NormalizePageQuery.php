<?php

namespace App\Http\Middleware;

use App\Support\PageToken;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Keeps paginated URLs clean and opaque:
 *
 *  - a page number in the URL is accepted as a plain number (legacy links)
 *    or as an encrypted PageToken, and is resolved before the request
 *    reaches the controller;
 *  - the first page is always served from the clean path (/audit) so a
 *    redundant "?page=1" never shows up in the address bar;
 *  - a legacy "?page=20" is redirected to its opaque token so page numbers
 *    never remain visible in the address bar.
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

        $raw = $request->query(PageToken::PARAM);

        if ($raw === null || $raw === '') {
            return $next($request);
        }

        $page = PageToken::decode($raw);

        // A value that is neither a number nor a token we issued.
        if ($page === null) {
            abort(404);
        }

        if ($page === 1) {
            return $this->redirectWith($request, null);
        }

        // Legacy plain page numbers are rewritten to their opaque token so the
        // page number never stays visible in the address bar.
        if (! PageToken::isToken($raw)) {
            return $this->redirectWith($request, PageToken::encode($page));
        }

        // Resolve the token so controllers and paginators see a normal page.
        $request->query->set(PageToken::PARAM, (string) $page);

        return $next($request);
    }

    /**
     * Redirect to the current path with the page parameter set to the given
     * value, or removed entirely when it is null.
     */
    private function redirectWith(Request $request, ?string $page): Response
    {
        $query = $request->query();

        if ($page === null) {
            unset($query[PageToken::PARAM]);
        } else {
            $query[PageToken::PARAM] = $page;
        }

        $url = '/'.ltrim($request->path(), '/');

        if ($query !== []) {
            $url .= '?'.http_build_query($query);
        }

        return redirect()->to($url, 301);
    }
}
