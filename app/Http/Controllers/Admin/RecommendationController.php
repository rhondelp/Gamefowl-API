<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreRecommendationRequest;
use App\Http\Requests\Admin\UpdateRecommendationRequest;
use App\Http\Resources\Admin\AdminRecommendationResource;
use App\Models\Recommendation;
use Illuminate\Http\JsonResponse;

class RecommendationController extends Controller
{
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
