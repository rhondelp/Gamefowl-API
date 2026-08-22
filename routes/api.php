<?php

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
    Route::prefix('auth')->group(function (): void {
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:6,1');

        Route::middleware('auth:sanctum')->group(function (): void {
            Route::post('/logout', [AuthController::class, 'logout']);
            Route::get('/me', [AuthController::class, 'me']);
        });
    });

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::apiResource('gamefowls', GamefowlController::class);

        Route::post('/gamefowls/{gamefowlId}/health-assessments', [HealthAssessmentController::class, 'store']);
        Route::get('/health-assessments/{id}', [HealthAssessmentController::class, 'show']);

        Route::post('/gamefowls/{gamefowlId}/health-records', [HealthRecordController::class, 'store']);
        Route::get('/gamefowls/{gamefowlId}/health-records', [HealthRecordController::class, 'index']);
        Route::get('/gamefowls/{gamefowlId}/health-history', [HealthHistoryController::class, 'history']);
        Route::get('/gamefowls/{gamefowlId}/health-status', [HealthHistoryController::class, 'status']);

        Route::get('/symptoms', [SymptomController::class, 'index']);
        Route::get('/diseases', [DiseaseController::class, 'index']);
        Route::get('/diseases/{id}', [DiseaseController::class, 'show']);

        Route::prefix('admin')->middleware('admin')->group(function (): void {
            Route::apiResource('symptoms', AdminSymptomController::class)
                ->only(['index', 'store', 'update', 'destroy']);

            Route::get('/diseases', [AdminDiseaseController::class, 'index']);
            Route::post('/diseases', [AdminDiseaseController::class, 'store']);
            Route::get('/diseases/{id}', [AdminDiseaseController::class, 'show']);
            Route::match(['put', 'patch'], '/diseases/{id}', [AdminDiseaseController::class, 'update']);
            Route::delete('/diseases/{id}', [AdminDiseaseController::class, 'destroy']);
            Route::post('/diseases/{id}/recommendations', [AdminDiseaseController::class, 'attachRecommendation']);
            Route::delete('/diseases/{id}/recommendations/{recommendationId}', [AdminDiseaseController::class, 'detachRecommendation']);

            Route::apiResource('recommendations', AdminRecommendationController::class)
                ->only(['index', 'store', 'update', 'destroy']);

            Route::post('/rules', [RuleController::class, 'store']);
            Route::match(['put', 'patch'], '/rules/{id}', [RuleController::class, 'update']);
            Route::delete('/rules/{id}', [RuleController::class, 'destroy']);

            Route::get('/dashboard', [DashboardController::class, 'index']);

            Route::get('/users', [UserController::class, 'index']);
            Route::get('/users/{id}', [UserController::class, 'show']);
            Route::match(['put', 'patch'], '/users/{id}', [UserController::class, 'update']);
            Route::delete('/users/{id}', [UserController::class, 'destroy']);
        });
    });
});
