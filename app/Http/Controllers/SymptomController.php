<?php

namespace App\Http\Controllers;

use App\Http\Resources\SymptomResource;
use App\Models\Symptom;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SymptomController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $symptoms = Symptom::active()->orderBy('category')->orderBy('name')->get();

        if ($request->boolean('grouped')) {
            $groups = $symptoms->groupBy('category')
                ->map(fn ($items) => SymptomResource::collection($items->values()));

            return response()->json([
                'success' => true,
                'message' => 'Symptoms retrieved successfully.',
                'data' => [
                    'groups' => $groups,
                ],
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Symptoms retrieved successfully.',
            'data' => [
                'items' => SymptomResource::collection($symptoms),
            ],
        ]);
    }
}
