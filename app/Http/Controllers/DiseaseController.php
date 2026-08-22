<?php

namespace App\Http\Controllers;

use App\Http\Resources\DiseaseResource;
use App\Models\Disease;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DiseaseController extends Controller
{
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
