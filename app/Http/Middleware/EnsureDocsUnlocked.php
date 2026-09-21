<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * File: app/Http/Middleware/EnsureDocsUnlocked.php
 *
 * Purpose:
 *   Keeps the documentation page (GET /documentation/view) behind its
 *   shared-password form. Visitors whose session has not been unlocked by
 *   POST /documentation are redirected to the form instead.
 *
 * How it fits into the project:
 *   Applied only to the documentation route in routes/web.php. This is a
 *   lightweight visibility gate, not authentication — see the comment above
 *   those routes for why that is enough here.
 */
class EnsureDocsUnlocked
{
    /**
     * Handle an incoming request.
     *
     * What it does: checks the session for the `docs_unlocked` flag set by
     * a correct password. Unlocked sessions continue to the page; everyone
     * else is sent to the password form.
     *
     * @param  Closure(Request): (Response)  $next  the next layer of the
     *         request pipeline; calling it means "allowed, continue".
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Strict comparison: only the exact flag written on unlock counts.
        if ($request->session()->get('docs_unlocked') !== true) {
            return redirect()->route('docs.gate');
        }

        return $next($request);
    }
}
