<?php

/**
 * File: routes/api.php
 *
 * Purpose:
 *   The complete map of the REST API. Every route lives under the /api/v1
 *   prefix (versioning: a future v2 would be added as a sibling group so the
 *   mobile app can migrate gradually).
 *
 * How protection works (read this first):
 *   - Public routes sit outside any middleware group.
 *   - `auth:sanctum` requires a valid bearer token; without one the request
 *     gets a 401 envelope.
 *   - `admin` is our custom alias (EnsureUserIsAdmin) and must come AFTER
 *     auth:sanctum — it assumes a user is already resolved and checks their
 *     role, returning 403 Forbidden for regular owners.
 *   - Ownership is NOT enforced here; it's enforced inside controllers via
 *     scoped queries + policies, which is why foreign IDs return generic 404s.
 */

use App\Http\Controllers\Admin\DiseaseController as AdminDiseaseController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\RecommendationController as AdminRecommendationController;
use App\Http\Controllers\Admin\RuleController;
use App\Http\Controllers\Admin\SymptomController as AdminSymptomController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\DiseaseController;
use App\Http\Controllers\GamefowlController;
use App\Http\Controllers\HealthAssessmentController;
use App\Http\Controllers\HealthHistoryController;
use App\Http\Controllers\HealthRecordController;
use App\Http\Controllers\SymptomController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    // ------------------------------------------------------------------
    // Authentication: register/login are public; logout/me need a token.
    // ------------------------------------------------------------------
    Route::prefix('auth')->group(function (): void {
        Route::post('/register', [AuthController::class, 'register']);
        // throttle:6,1 limits this route to 6 attempts per minute per IP,
        // blunting brute-force password guessing.
        Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:6,1');

        Route::middleware('auth:sanctum')->group(function (): void {
            Route::post('/logout', [AuthController::class, 'logout']);
            Route::get('/me', [AuthController::class, 'me']);
        });
    });

    // ------------------------------------------------------------------
    // Everything below requires a valid bearer token.
    // ------------------------------------------------------------------
    Route::middleware('auth:sanctum')->group(function (): void {
        // Owner's bird profiles (CRUD).
        Route::apiResource('gamefowls', GamefowlController::class);

        // Symptom-based diagnostic flow: submit for one of YOUR birds,
        // then read the stored assessment by its own ID.
        Route::post('/gamefowls/{gamefowlId}/health-assessments', [HealthAssessmentController::class, 'store']);
        Route::get('/health-assessments/{id}', [HealthAssessmentController::class, 'show']);

        // Manual logbook entries + merged timeline + derived status.
        Route::post('/gamefowls/{gamefowlId}/health-records', [HealthRecordController::class, 'store']);
        Route::get('/gamefowls/{gamefowlId}/health-records', [HealthRecordController::class, 'index']);
        Route::get('/gamefowls/{gamefowlId}/health-history', [HealthHistoryController::class, 'history']);
        Route::get('/gamefowls/{gamefowlId}/health-status', [HealthHistoryController::class, 'status']);

        // Knowledge-base reads: owners see active entries only (weights hidden).
        Route::get('/symptoms', [SymptomController::class, 'index']);
        Route::get('/diseases', [DiseaseController::class, 'index']);
        Route::get('/diseases/{id}', [DiseaseController::class, 'show']);

        // ------------------------------------------------------------------
        // Admin-only surface. The 'admin' alias runs after auth:sanctum and
        // rejects regular owners with 403 Forbidden.
        // ------------------------------------------------------------------
        Route::prefix('admin')->middleware('admin')->group(function (): void {
            // Knowledge base management (Milestone 4).
            Route::apiResource('symptoms', AdminSymptomController::class)
                ->only(['index', 'store', 'update', 'destroy']);

            // Diseases get custom routes so recommendation attach/detach can
            // nest under them; match() accepts both PUT and PATCH verbs.
            Route::get('/diseases', [AdminDiseaseController::class, 'index']);
            Route::post('/diseases', [AdminDiseaseController::class, 'store']);
            Route::get('/diseases/{id}', [AdminDiseaseController::class, 'show']);
            Route::match(['put', 'patch'], '/diseases/{id}', [AdminDiseaseController::class, 'update']);
            Route::delete('/diseases/{id}', [AdminDiseaseController::class, 'destroy']);
            Route::post('/diseases/{id}/recommendations', [AdminDiseaseController::class, 'attachRecommendation']);
            Route::delete('/diseases/{id}/recommendations/{recommendationId}', [AdminDiseaseController::class, 'detachRecommendation']);

            Route::apiResource('recommendations', AdminRecommendationController::class)
                ->only(['index', 'store', 'update', 'destroy']);

            // Weighted knowledge-base rules (the engine's brain).
            Route::post('/rules', [RuleController::class, 'store']);
            Route::match(['put', 'patch'], '/rules/{id}', [RuleController::class, 'update']);
            Route::delete('/rules/{id}', [RuleController::class, 'destroy']);

            // User management + dashboard (Milestone 8).
            Route::get('/dashboard', [DashboardController::class, 'index']);

            Route::get('/users', [UserController::class, 'index']);
            Route::get('/users/{id}', [UserController::class, 'show']);
            Route::match(['put', 'patch'], '/users/{id}', [UserController::class, 'update']);
            Route::delete('/users/{id}', [UserController::class, 'destroy']);
        });
    });
});
