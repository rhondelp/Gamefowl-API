<?php

/**
 * File: bootstrap/app.php
 *
 * Purpose:
 *   Application bootstrap configuration for this Laravel install. This is
 *   where three project-wide behaviors are configured:
 *
 *   1. ROUTING — which files define web/API/console routes.
 *   2. MIDDLEWARE — named aliases usable in route definitions.
 *   3. EXCEPTIONS — how errors are rendered as JSON.
 *
 * How it fits into the project:
 *   The exception renderers below implement the API's consistent response
 *   envelope ({success, message, ...}) for EVERY error type on /api/*
 *   routes, so the mobile app only ever has to parse one shape. Web routes
 *   keep Laravel's defaults (they aren't used by the mobile app).
 */

use App\Http\Middleware\EnsureUserIsAdmin;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        // Route files: api.php holds everything the mobile app consumes;
        // web.php remains the framework default and is unused by the app.
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Named middleware aliases usable in route definitions:
        // 'admin' gates every /api/v1/admin/* route by checking
        // $user->isAdmin() AFTER auth:sanctum has resolved the user.
        $middleware->alias([
            'admin' => EnsureUserIsAdmin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        /*
         | --------------------------------------------------------------
         | Error rendering for API routes
         | --------------------------------------------------------------
         | Each closure converts one framework exception into our standard
         | JSON envelope. They are checked top to bottom; the first match
         | wins. All are limited to $request->is('api/*') so non-API pages
         * keep Laravel's default behavior.
         |
         * IMPORTANT framework detail discovered while building Milestone 6:
         * before these callbacks run, Laravel ALREADY converts some
         * exceptions — AuthorizationException becomes
         * AccessDeniedHttpException(403) and ModelNotFoundException becomes
         * NotFoundHttpException(404). That is why policy denials are caught
         * via the Symfony types below rather than Illuminate's originals.
         */
        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->is('api/*')) {
                // Form Request failures land here: 422 with per-field errors.
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $e->errors(),
                ], 422);
            }
        });

        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*')) {
                // Missing/expired/invalid token on a protected route.
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated.',
                ], 401);
            }
        });

        /*
         * Both of these become the SAME generic 404 body on purpose
         * (anti-enumeration): callers must not be able to distinguish "this
         * record exists but belongs to someone else" from "no such record".
         * - AccessDeniedHttpException: a policy denied an action.
         * - NotFoundHttpException: findOrFail() found nothing (Laravel also
         *   converts ModelNotFoundException into this before callbacks run).
         */
        $exceptions->render(function (AccessDeniedHttpException | NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Resource not found.',
                ], 404);
            }
        });
    })->create();
