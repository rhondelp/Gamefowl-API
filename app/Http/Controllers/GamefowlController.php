<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreGamefowlRequest;
use App\Http\Requests\UpdateGamefowlRequest;
use App\Http\Resources\GamefowlResource;
use App\Models\Gamefowl;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * File: app/Http/Controllers/GamefowlController.php
 *
 * Purpose:
 *   CRUD endpoints for a user's own gamefowl profiles:
 *     GET/POST /api/v1/gamefowls
 *     GET/PUT/PATCH/DELETE /api/v1/gamefowls/{id}
 *
 * How it fits into the project:
 *   Every query is scoped through $request->user()->gamefowls() — the
 *   ownership pattern used across the whole API. Because lookups run inside
 *   the relationship, another owner's bird simply "does not exist" for the
 *   caller (404), which is our anti-enumeration stance. Policies
 *   (GamefowlPolicy) are applied on top as defense-in-depth.
 */
class GamefowlController extends Controller
{
    /**
     * List the authenticated owner's birds.
     *
     * Default behavior: only ACTIVE birds are returned (is_active = true).
     * The optional ?include_inactive=1 flag shows retired birds too, so an
     * owner can find and re-activate them. Soft-DELETED birds never appear
     * here in either mode (Eloquent's SoftDeletes scope hides them).
     *
     * Paginated at 15 per page; items shaped by GamefowlResource.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Gamefowl::class);

        $query = $request->user()->gamefowls();

        // Unless the caller explicitly asks for retired birds, filter them out.
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

    /**
     * Create a bird for the authenticated owner.
     *
     * Ownership note: the bird is created THROUGH the relationship
     * ($request->user()->gamefowls()->create(...)), which sets user_id from
     * the token. A `user_id` in the request body is never read — tested by
     * CreateGamefowlTest::test_user_id_cannot_be_spoofed_via_payload.
     *
     * Returns 201 with the created resource; is_active always starts true.
     */
    public function store(StoreGamefowlRequest $request): JsonResponse
    {
        $this->authorize('create', Gamefowl::class);

        // Explicit default: new birds start life as active roster members.
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

    /**
     * Show one of the caller's birds.
     *
     * Two safety layers: (1) the scoped lookup makes other owners' birds 404,
     * and (2) the policy re-checks ownership. A missing OR foreign ID both
     * produce the same generic 404 envelope (anti-enumeration).
     */
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

    /**
     * Update one of the caller's birds (partial update allowed — send only
     * the fields you want changed). Setting "is_active": false is how an
     * owner retires a bird without deleting it; it then disappears from the
     * default list but remains reachable via ?include_inactive=1.
     */
    public function update(UpdateGamefowlRequest $request, int $id): JsonResponse
    {
        $gamefowl = $request->user()->gamefowls()->findOrFail($id);
        $this->authorize('update', $gamefowl);

        $gamefowl->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Gamefowl updated successfully.',
            'data' => [
                // fresh() re-reads from DB so casts (weight format etc.)
                // reflect stored values exactly.
                'gamefowl' => new GamefowlResource($gamefowl->fresh()),
            ],
        ]);
    }

    /**
     * Soft-delete one of the caller's birds.
     *
     * Why soft delete: assessments reference this bird forever; hard-deleting
     * would orphan medical history. The row stays (hidden everywhere), and
     * the response confirms with a plain success envelope.
     */
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
