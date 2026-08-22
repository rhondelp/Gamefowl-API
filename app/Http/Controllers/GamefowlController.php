<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreGamefowlRequest;
use App\Http\Requests\UpdateGamefowlRequest;
use App\Http\Resources\GamefowlResource;
use App\Models\Gamefowl;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GamefowlController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Gamefowl::class);

        $query = $request->user()->gamefowls();

        if (! $request->boolean('include_inactive')) {
            $query->where('is_active', true);
        }

        $gamefowls = $query->latest()->paginate(15);

        return response()->json([
            'success' => true,
            'message' => 'Gamefowls retrieved successfully.',
            'data' => [
                'items' => GamefowlResource::collection($gamefowls->items()),
                'pagination' => [
                    'current_page' => $gamefowls->currentPage(),
                    'last_page' => $gamefowls->lastPage(),
                    'per_page' => $gamefowls->perPage(),
                    'total' => $gamefowls->total(),
                ],
            ],
        ]);
    }

    public function store(StoreGamefowlRequest $request): JsonResponse
    {
        $this->authorize('create', Gamefowl::class);

        $gamefowl = $request->user()->gamefowls()->create([
            ...$request->validated(),
            'is_active' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Gamefowl created successfully.',
            'data' => [
                'gamefowl' => new GamefowlResource($gamefowl),
            ],
        ], 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $gamefowl = $request->user()->gamefowls()->findOrFail($id);
        $this->authorize('view', $gamefowl);

        return response()->json([
            'success' => true,
            'message' => 'Gamefowl retrieved successfully.',
            'data' => [
                'gamefowl' => new GamefowlResource($gamefowl),
            ],
        ]);
    }

    public function update(UpdateGamefowlRequest $request, int $id): JsonResponse
    {
        $gamefowl = $request->user()->gamefowls()->findOrFail($id);
        $this->authorize('update', $gamefowl);

        $gamefowl->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Gamefowl updated successfully.',
            'data' => [
                'gamefowl' => new GamefowlResource($gamefowl->fresh()),
            ],
        ]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $gamefowl = $request->user()->gamefowls()->findOrFail($id);
        $this->authorize('delete', $gamefowl);

        $gamefowl->delete();

        return response()->json([
            'success' => true,
            'message' => 'Gamefowl deleted successfully.',
        ]);
    }
}
