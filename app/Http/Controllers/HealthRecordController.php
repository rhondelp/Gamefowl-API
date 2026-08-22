<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreHealthRecordRequest;
use App\Http\Resources\HealthRecordResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HealthRecordController extends Controller
{
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

    public function store(StoreHealthRecordRequest $request, int $gamefowlId): JsonResponse
    {
        $gamefowl = $request->user()->gamefowls()->findOrFail($gamefowlId);
        $this->authorize('view', $gamefowl);

        $record = $gamefowl->healthRecords()->create([
            ...$request->validated(),
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
