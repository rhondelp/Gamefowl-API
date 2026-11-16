<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * File: app/Http/Middleware/SetNoIndexHeader.php
 *
 * Purpose:
 *   Adds `X-Robots-Tag: noindex, nofollow` to every response of the routes it
 *   wraps: the documentation pages (including the password form and the
 *   redirect to it), the password-reset pages, and the APK binary.
 *
 * How it fits into the project:
 *   The HTML pages also carry <meta name="robots"> via partials/seo, but a
 *   header covers redirects and non-HTML responses too. The documentation
 *   path is deliberately NOT listed in robots.txt (that would advertise it),
 *   so this header plus the meta tag is what keeps it out of search results.
 */
class SetNoIndexHeader
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Robots-Tag', 'noindex, nofollow');

        return $response;
    }
}
