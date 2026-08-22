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

/**
 * File: app/Http/Controllers/Admin/DiseaseController.php
 *
 * Purpose:
 *   Admin CRUD for diseases, PLUS recommendation attach/detach:
 *     GET    /admin/diseases            — all diseases (incl. inactive)
 *     POST   /admin/diseases            — create
 *     GET    /admin/diseases/{id}       — full detail incl. rule WEIGHTS
 *     PUT    /admin/diseases/{id}       — update (also re-activate)
 *     DELETE /admin/diseases/{id}       — deactivate (never hard delete)
 *     POST   /admin/diseases/{id}/recommendations              — link advice
 *     DELETE /admin/diseases/{id}/recommendations/{recId}      — unlink advice
 *
 * How it fits into the project:
 *   This is the admin counterpart to DiseaseController. The key difference:
 *   payloads here INCLUDE internal data owners never see — rule weights,
 *   is_active flags, and timestamps.
 */
class DiseaseController extends Controller
{
    /**
     * List every disease with its linked recommendations eager-loaded (one
     * extra query instead of N+1). AdminDiseaseResource exposes weights and
     * is_active per entry.
     */
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

    /**
     * Full detail for one disease: informational fields plus its complete
     * rule set (each row: rule_id, symptom_id, symptom_name, weight) and
     * attached recommendations. This is what an admin uses to audit/tune
     * how the engine scores this condition.
     */
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

    /**
     * Create a disease from validated input; starts active. Returns 201.
     */
    public function store(StoreDiseaseRequest $request): JsonResponse
    {
        $disease = Disease::create([
            ...$request->validated(),
            // Explicit so the response shows the flag without a re-read.
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

    /**
     * Update a disease (partial). Also serves as "re-activate" when called
     * with {"is_active": true}.
     */
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

    /**
     * Deactivate a disease. It stops appearing for owners and can no longer
     * be suggested by the engine (active-only filter), while historical
     * assessments keep their stored snapshots untouched.
     */
    public function destroy(int $id): JsonResponse
    {
        $disease = Disease::findOrFail($id);
        $disease->update(['is_active' => false]);

        return response()->json([
            'success' => true,
            'message' => 'Disease deactivated successfully.',
        ]);
    }

    /**
     * Attach an existing recommendation to a disease.
     *
     * Duplicate links are rejected with 422 instead of silently succeeding,
     * because a repeated pair would either violate the DB unique constraint
     * or confuse admins about intent.
     */
    public function attachRecommendation(Request $request, int $id): JsonResponse
    {
        // Inline validation here (rather than a Form Request class) because
        // it's a single field; exists() guarantees the recommendation is real.
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

    /**
     * Detach a recommendation from a disease. Idempotent by design: detaching
     * something already detached still reports success (the end state is what
     * matters for admin UIs).
     */
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
