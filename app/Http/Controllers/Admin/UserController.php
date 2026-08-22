<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateUserByAdminRequest;
use App\Http\Resources\Admin\AdminUserDetailResource;
use App\Http\Resources\Admin\AdminUserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $users = User::query()
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

    public function show(int $id): JsonResponse
    {
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

    public function update(UpdateUserByAdminRequest $request, int $id): JsonResponse
    {
        if ((int) $id === $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Admins cannot modify their own account through this endpoint.',
            ], 409);
        }

        $user = User::withTrashed()->findOrFail($id);
        $validated = $request->validated();

        if (array_key_exists('role', $validated)) {
            $user->role = $validated['role'];
        }

        if (($validated['status'] ?? null) === 'inactive') {
            $user->delete();
        } elseif (($validated['status'] ?? null) === 'active') {
            $user->restore();
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
