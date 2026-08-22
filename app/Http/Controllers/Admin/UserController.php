<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateUserByAdminRequest;
use App\Http\Resources\Admin\AdminUserDetailResource;
use App\Http\Resources\Admin\AdminUserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * File: app/Http/Controllers/Admin/UserController.php
 *
 * Purpose:
 *   Admin user management:
 *     GET    /api/v1/admin/users        — paginated list, filterable
 *     GET    /api/v1/admin/users/{id}   — detail with aggregate counts
 *     PATCH  /api/v1/admin/users/{id}   — change role and/or active status
 *     DELETE /api/v1/admin/users/{id}   — deactivate (soft delete)
 *
 * How it fits into the project:
 *   Completes the admin surface from Milestone 8. Deactivating a user means
 *   soft-deleting (deleted_at stamped) — the account disappears from normal
 *   listings but the row survives so their gamefowl/assessment history stays
 *   valid. "status": "active" on an inactive account restores it.
 */
class UserController extends Controller
{
    /**
     * Paginated list of accounts.
     *
     * Filters:
     * - ?role=owner|admin narrows to one role.
     * - ?status=inactive shows ONLY deactivated accounts; the default shows
     *   only active ones (SoftDeletes hides trashed rows automatically).
     *
     * per_page is client-tunable but capped at 100.
     */
    public function index(Request $request): JsonResponse
    {
        $users = User::query()
            // when() applies the closure only if the query param is present.
            ->when(
                $request->query('role'),
                fn ($query, $role) => $query->where('role', $role)
            )
            ->when(
                $request->query('status') === 'inactive',
                fn ($query) => $query->onlyTrashed()
            )
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(min(100, max(1, (int) $request->query('per_page', 15))));

        return response()->json([
            'success' => true,
            'message' => 'Users retrieved successfully.',
            'data' => [
                'items' => AdminUserResource::collection($users->items()),
                'pagination' => [
                    'current_page' => $users->currentPage(),
                    'last_page' => $users->lastPage(),
                    'per_page' => $users->perPage(),
                    'total' => $users->total(),
                ],
            ],
        ]);
    }

    /**
     * One account's detail plus light aggregate counts (how many birds they
     * own, how many assessments exist across those birds). Counts come from
     * SQL COUNT via withCount() — the actual records are never loaded, so
     * the payload stays small no matter how active the account is.
     */
    public function show(int $id): JsonResponse
    {
        // withTrashed() lets admins inspect deactivated accounts too.
        $user = User::withTrashed()
            ->withCount(['gamefowls', 'healthAssessments'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'message' => 'User retrieved successfully.',
            'data' => [
                'user' => new AdminUserDetailResource($user),
            ],
        ]);
    }

    /**
     * Change a user's role and/or active status.
     *
     * Self-lockout prevention: an admin cannot modify their OWN account here
     * (demoting or deactivating yourself would lock you out of the admin
     * panel with no one left to undo it). This returns a direct 409 JSON
     * response instead of throwing — our error renderer would otherwise turn
     * AuthorizationExceptions into generic 404s.
     */
    public function update(UpdateUserByAdminRequest $request, int $id): JsonResponse
    {
        if ((int) $id === $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Admins cannot modify their own account through this endpoint.',
            ], 409);
        }

        // withTrashed(): admins may re-activate a deactivated account by
        // sending {"status": "active"} (restore() clears deleted_at).
        $user = User::withTrashed()->findOrFail($id);
        $validated = $request->validated();

        if (array_key_exists('role', $validated)) {
            $user->role = $validated['role'];
        }

        if (($validated['status'] ?? null) === 'inactive') {
            $user->delete();      // soft delete = deactivation
        } elseif (($validated['status'] ?? null) === 'active') {
            $user->restore();     // clears deleted_at
        }

        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'User updated successfully.',
            'data' => [
                'user' => new AdminUserResource($user),
            ],
        ]);
    }

    /**
     * Deactivate another account (soft delete).
     *
     * Same self-action guard as update(): an admin deleting themselves would
     * be instant self-lockout. The account can be restored later via PATCH
     * {"status": "active"}, and all of its birds/assessments remain intact.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        if ((int) $id === $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Admins cannot deactivate their own account.',
            ], 409);
        }

        $user = User::withTrashed()->findOrFail($id);
        $user->delete(); // soft delete = deactivation

        return response()->json([
            'success' => true,
            'message' => 'User deactivated successfully.',
        ]);
    }
}
