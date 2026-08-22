<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSymptomRequest;
use App\Http\Requests\Admin\UpdateSymptomRequest;
use App\Http\Resources\Admin\AdminSymptomResource;
use App\Models\Symptom;
use Illuminate\Http\JsonResponse;

/**
 * File: app/Http/Controllers/Admin/SymptomController.php
 *
 * Purpose:
 *   Admin CRUD for the knowledge base's symptom list:
 *     GET    /api/v1/admin/symptoms        — ALL symptoms (active AND inactive)
 *     POST   /api/v1/admin/symptoms        — create
 *     PUT    /api/v1/admin/symptoms/{id}   — update (also used to re-activate
 *                                            via "is_active": true)
 *     DELETE /api/v1/admin/symptoms/{id}   — DEACTIVATE, never hard delete
 *
 * How it fits into the project:
 *   Every route here sits behind auth:sanctum + the 'admin' middleware.
 *   Owners see symptoms through SymptomController instead, which hides
 *   inactive entries and never exposes admin-only fields. Deactivation (not
 *   deletion) is the removal convention across this project so historical
 *   assessment snapshots stay meaningful.
 */
class SymptomController extends Controller
{
    /**
     * Full symptom list for admin screens. Unlike the owner-facing endpoint,
     * this includes deactivated rows (flagged by is_active) so admins can
     * find and re-activate them.
     */
    public function index(): JsonResponse
    {
        $symptoms = Symptom::orderBy('category')->orderBy('name')->get();

        return response()->json([
            'success' => true,
            'message' => 'Symptoms retrieved successfully.',
            'data' => [
                'items' => AdminSymptomResource::collection($symptoms),
            ],
        ]);
    }

    /**
     * Create a symptom from validated admin input.
     *
     * is_active defaults to true explicitly so the response shows the flag
     * immediately (the DB default only fills the column, not the in-memory
     * model we serialize).
     */
    public function store(StoreSymptomRequest $request): JsonResponse
    {
        $symptom = Symptom::create([
            ...$request->validated(),
            'is_active' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Symptom created successfully.',
            'data' => [
                'symptom' => new AdminSymptomResource($symptom),
            ],
        ], 201);
    }

    /**
     * Update an existing symptom (partial update). Also doubles as the
     * "re-activate" action when called with {"is_active": true}, since
     * UpdateSymptomRequest accepts that field for admins.
     */
    public function update(UpdateSymptomRequest $request, int $id): JsonResponse
    {
        $symptom = Symptom::findOrFail($id);
        $symptom->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Symptom updated successfully.',
            'data' => [
                // fresh() re-reads so the response reflects stored state.
                'symptom' => new AdminSymptomResource($symptom->fresh()),
            ],
        ]);
    }

    /**
     * Deactivate a symptom (project-wide removal convention).
     *
     * The row stays in the database: past assessments that reference it keep
     * their snapshot data, but it disappears from owner-facing lists and can
     * no longer be selected in new submissions. Rules pointing at inactive
     * symptoms are ignored by the engine automatically.
     */
    public function destroy(int $id): JsonResponse
    {
        $symptom = Symptom::findOrFail($id);
        $symptom->update(['is_active' => false]);

        return response()->json([
            'success' => true,
            'message' => 'Symptom deactivated successfully.',
        ]);
    }
}
