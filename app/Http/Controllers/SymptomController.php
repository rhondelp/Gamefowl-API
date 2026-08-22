<?php

namespace App\Http\Controllers;

use App\Http\Resources\SymptomResource;
use App\Models\Symptom;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * File: app/Http/Controllers/SymptomController.php
 *
 * Purpose:
 *   Owner-facing READ endpoint for symptoms:
 *     GET /api/v1/symptoms          — flat list
 *     GET /api/v1/symptoms?grouped=1 — list grouped by category
 *
 * How it fits into the project:
 *   The mobile app calls this when an owner reports a sick bird: the screen
 *   shows the checklist of signs to tick before submitting a health
 *   assessment. Only ACTIVE symptoms appear (deactivated ones are hidden);
 *   rule weights are never exposed here — that data stays admin-only.
 */
class SymptomController extends Controller
{
    /**
     * Return all active symptoms, ordered by category then name so the
     * checklist renders in a stable, grouped order.
     *
     * With ?grouped=1, items are nested under their category name
     * (e.g. { "groups": { "respiratory": [...], "physical": [...] } }),
     * which saves the mobile app from grouping client-side.
     */
    public function index(Request $request): JsonResponse
    {
        $symptoms = Symptom::active()->orderBy('category')->orderBy('name')->get();

        if ($request->boolean('grouped')) {
            // groupBy('category') produces one collection per category;
            // ->values() reindexes each group from 0 for clean JSON arrays.
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
