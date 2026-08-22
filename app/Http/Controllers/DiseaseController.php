<?php

namespace App\Http\Controllers;

use App\Http\Resources\DiseaseResource;
use App\Models\Disease;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * File: app/Http/Controllers/DiseaseController.php
 *
 * Purpose:
 *   Owner-facing READ endpoints for diseases (educational/reference data):
 *     GET /api/v1/diseases       — active diseases
 *     GET /api/v1/diseases/{id}  — one disease + its symptom names
 *
 * How it fits into the project:
 *   Owners can browse what conditions the system knows about and what
 *   general guidance exists for each. Rule WEIGHTS are intentionally absent
 *   from every payload here — they are internal engine tuning visible only
 *   through /admin/diseases (see Admin\DiseaseController).
 */
class DiseaseController extends Controller
{
    /**
     * List all ACTIVE diseases alphabetically.
     *
     * Deactivated diseases vanish from this list immediately, but any
     * historical assessment that referenced them still displays its stored
     * snapshot (see HealthAssessmentResult) — history never changes.
     */
    public function index(Request $request): JsonResponse
    {
        $diseases = Disease::active()->orderBy('name')->get();

        return response()->json([
            'success' => true,
            'message' => 'Diseases retrieved successfully.',
            'data' => [
                'items' => DiseaseResource::collection($diseases),
            ],
        ]);
    }

    /**
     * Show one disease with the names of its associated active symptoms.
     *
     * Scope details:
     * - where('is_active', true) on the disease itself means deactivated
     *   entries 404 for owners even if the ID is valid.
     * - The eager-loaded symptoms are filtered the same way, and only
     *   id/name/category are exposed — no weights, per the privacy rule above.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $disease = Disease::active()
            ->with(['symptoms' => fn ($query) => $query->where('is_active', true)])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'message' => 'Disease retrieved successfully.',
            'data' => [
                'disease' => [
                    ...(new DiseaseResource($disease))->toArray($request),
                    'symptoms' => $disease->symptoms->map(fn ($symptom) => [
                        'id' => $symptom->id,
                        'name' => $symptom->name,
                        'category' => $symptom->category,
                    ]),
                ],
            ],
        ]);
    }
}
