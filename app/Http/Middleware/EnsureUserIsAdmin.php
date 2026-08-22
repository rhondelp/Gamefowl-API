<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * File: app/Http/Middleware/EnsureUserIsAdmin.php
 *
 * Purpose:
 *   Blocks any request from reaching an admin-only endpoint unless the
 *   authenticated user has role = 'admin'. Registered under the alias name
 *   'admin' in bootstrap/app.php.
 *
 * How it fits into the project:
 *   Every route inside routes/api.php's `->middleware('admin')` group runs
 *   through this class AFTER auth:sanctum has already resolved the user —
 *   so `$request->user()` is guaranteed non-null here. It protects the M4
 *   knowledge-base CRUD, and the M8 user-management/dashboard endpoints.
 */
class EnsureUserIsAdmin
{
    /**
     * Handle an incoming request.
     *
     * What it does: checks the authenticated user's role. Admins pass
     * straight through to the controller; everyone else receives a JSON
     * 403 envelope and the route never runs.
     *
     * Why a direct response instead of an exception: throwing
     * AuthorizationException here would get converted by Laravel's error
     * renderer into our generic 404 "Resource not found." envelope (the
     * anti-enumeration choice for owner resources). For ADMIN endpoints we
     * want the honest, explicit 403 Forbidden — so we return it directly.
     * The tests in KnowledgeBase\AdminCrudTest and Admin\AdminUserManagementTest
     * pin this exact body.
     *
     * @param  Closure(Request): (Response)  $next  the next layer of the
     *         request pipeline; calling it means "allowed, continue".
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Null-safe check: if no user is set, treat as non-admin.
        // (auth:sanctum normally prevents this case from ever occurring.)
        if (! $request->user()?->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden.',
            ], 403);
        }

        return $next($request);
    }
}
