<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreHealthRecordRequest;
use App\Http\Resources\HealthRecordResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * File: app/Http/Controllers/HealthRecordController.php
 *
 * Purpose:
 *   Manual health logbook entries for a bird:
 *     POST /api/v1/gamefowls/{id}/health-records — create (vet visit,
 *         weight check, vaccination, or free note; backdating allowed)
 *     GET  /api/v1/gamefowls/{id}/health-records — paginated list
 *
 * How it fits into the project:
 *   These are the HUMAN-entered records, distinct from engine-generated
 *   assessments. HealthHistoryController merges them with assessments into
 *   the timeline and uses the newest one as context in /health-status.
 *
 * Ownership pattern: both methods resolve the bird through
 * $request->user()->gamefowls(), so another owner's bird 404s exactly like
 * a nonexistent one. Authorization uses GamefowlPolicy::view directly —
 * records have no independent access path of their own.
 */
class HealthRecordController extends Controller
{
    /**
     * List a bird's manual health records, newest first.
     *
     * Sorted by recorded_at (the date the OWNER says things happened), not
     * created_at — backdating is the whole point of this feature. per_page
     * is client-tunable but capped at 100 to keep responses light.
     */
    public function index(Request $request, int $gamefowlId): JsonResponse
    {
        $gamefowl = $request->user()->gamefowls()->findOrFail($gamefowlId);
        $this->authorize('view', $gamefowl);

        $perPage = min(100, max(1, (int) $request->query('per_page', 15)));
        $records = $gamefowl->healthRecords()
            ->orderByDesc('recorded_at')
            ->orderByDesc('id')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Health records retrieved successfully.',
            'data' => [
                'items' => HealthRecordResource::collection($records->items()),
                'pagination' => [
                    'current_page' => $records->currentPage(),
                    'last_page' => $records->lastPage(),
                    'per_page' => $records->perPage(),
                    'total' => $records->total(),
                ],
            ],
        ]);
    }

    /**
     * Create a manual logbook entry for the bird.
     *
     * recorded_at defaults to TODAY when the client omits it ("logging
     * right now"); supplying an earlier date lets owners record last week's
     * vet visit. Future dates are rejected by StoreHealthRecordRequest —
     * you cannot log something that has not happened yet.
     */
    public function store(StoreHealthRecordRequest $request, int $gamefowlId): JsonResponse
    {
        $gamefowl = $request->user()->gamefowls()->findOrFail($gamefowlId);
        $this->authorize('view', $gamefowl);

        $record = $gamefowl->healthRecords()->create([
            ...$request->validated(),
            // Fallback when the caller didn't specify when it happened.
            'recorded_at' => $request->validated('recorded_at') ?? now()->toDateString(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Health record created successfully.',
            'data' => [
                'record' => new HealthRecordResource($record),
            ],
        ], 201);
    }
}
