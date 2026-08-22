<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreRecommendationRequest;
use App\Http\Requests\Admin\UpdateRecommendationRequest;
use App\Http\Resources\Admin\AdminRecommendationResource;
use App\Models\Recommendation;
use Illuminate\Http\JsonResponse;

/**
 * File: app/Http/Controllers/Admin/RecommendationController.php
 *
 * Purpose:
 *   Admin CRUD for care-advice entries ("Isolate affected birds", etc.):
 *     GET    /admin/recommendations        — all (incl. inactive)
 *     POST   /admin/recommendations        — create
 *     PUT    /admin/recommendations/{id}   — update / re-activate
 *     DELETE /admin/recommendations/{id}   — deactivate (never hard delete)
 *
 * How it fits into the project:
 *   Recommendations carry no scoring weight; they are guidance content that
 *   admins attach to diseases via Admin\DiseaseController and that ride
 *   along in assessment output for the owner's benefit.
 */
class RecommendationController extends Controller
{
    /**
     * List every recommendation, grouped visually by category then title.
     */
    public function index(): JsonResponse
    {
        $recommendations = Recommendation::orderBy('category')->orderBy('title')->get();

        return response()->json([
            'success' => true,
            'message' => 'Recommendations retrieved successfully.',
            'data' => [
                'items' => AdminRecommendationResource::collection($recommendations),
            ],
        ]);
    }

    /**
     * Create a recommendation; starts active (explicit so the response shows it).
     */
    public function store(StoreRecommendationRequest $request): JsonResponse
    {
        $recommendation = Recommendation::create([
            ...$request->validated(),
            'is_active' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Recommendation created successfully.',
            'data' => [
                'recommendation' => new AdminRecommendationResource($recommendation),
            ],
        ], 201);
    }

    /**
     * Partial update; also the re-activation path ({"is_active": true}).
     */
    public function update(UpdateRecommendationRequest $request, int $id): JsonResponse
    {
        $recommendation = Recommendation::findOrFail($id);
        $recommendation->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Recommendation updated successfully.',
            'data' => [
                'recommendation' => new AdminRecommendationResource($recommendation->fresh()),
            ],
        ]);
    }

    /**
     * Deactivate. Linked diseases keep their pivot rows, but deactivated
     * advice stops being shown to owners.
     */
    public function destroy(int $id): JsonResponse
    {
        $recommendation = Recommendation::findOrFail($id);
        $recommendation->update(['is_active' => false]);

        return response()->json([
            'success' => true,
            'message' => 'Recommendation deactivated successfully.',
        ]);
    }
}
