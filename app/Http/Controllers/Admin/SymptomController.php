<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSymptomRequest;
use App\Http\Requests\Admin\UpdateSymptomRequest;
use App\Http\Resources\Admin\AdminSymptomResource;
use App\Models\Symptom;
use Illuminate\Http\JsonResponse;

class SymptomController extends Controller
{
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

    public function update(UpdateSymptomRequest $request, int $id): JsonResponse
    {
        $symptom = Symptom::findOrFail($id);
        $symptom->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Symptom updated successfully.',
            'data' => [
                'symptom' => new AdminSymptomResource($symptom->fresh()),
            ],
        ]);
    }

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
