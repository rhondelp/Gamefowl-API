<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreDiseaseRequest;
use App\Http\Requests\Admin\UpdateDiseaseRequest;
use App\Http\Resources\Admin\AdminDiseaseResource;
use App\Models\Disease;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class DiseaseController extends Controller
{
    public function index(): JsonResponse
    {
        $diseases = Disease::with('recommendations')->orderBy('name')->get();

        return response()->json([
            'success' => true,
            'message' => 'Diseases retrieved successfully.',
            'data' => [
                'items' => AdminDiseaseResource::collection($diseases),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $disease = Disease::with(['symptoms', 'recommendations'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'message' => 'Disease retrieved successfully.',
            'data' => [
                'disease' => new AdminDiseaseResource($disease),
            ],
        ]);
    }

    public function store(StoreDiseaseRequest $request): JsonResponse
    {
        $disease = Disease::create([
            ...$request->validated(),
            'is_active' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Disease created successfully.',
            'data' => [
                'disease' => new AdminDiseaseResource($disease),
            ],
        ], 201);
    }

    public function update(UpdateDiseaseRequest $request, int $id): JsonResponse
    {
        $disease = Disease::findOrFail($id);
        $disease->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Disease updated successfully.',
            'data' => [
                'disease' => new AdminDiseaseResource($disease),
            ],
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $disease = Disease::findOrFail($id);
        $disease->update(['is_active' => false]);

        return response()->json([
            'success' => true,
            'message' => 'Disease deactivated successfully.',
        ]);
    }

    public function attachRecommendation(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'recommendation_id' => ['required', 'integer', 'exists:recommendations,id'],
        ]);

        $disease = Disease::findOrFail($id);
        $exists = $disease->recommendations()
            ->where('recommendation_id', $request->integer('recommendation_id'))
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'recommendation_id' => ['This recommendation is already attached to the disease.'],
            ]);
        }

        $disease->recommendations()->attach($request->integer('recommendation_id'));

        return response()->json([
            'success' => true,
            'message' => 'Recommendation attached successfully.',
        ], 201);
    }

    public function detachRecommendation(int $id, int $recommendationId): JsonResponse
    {
        $disease = Disease::findOrFail($id);
        $disease->recommendations()->detach($recommendationId);

        return response()->json([
            'success' => true,
            'message' => 'Recommendation detached successfully.',
        ]);
    }
}
