<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * File: app/Http/Requests/Admin/UpdateUserByAdminRequest.php
 *
 * Purpose:
 *   Validates PATCH /api/v1/admin/users/{id} — the admin's only levers over
 *   another account: its role and its active status.
 *
 * How it fits into the project:
 *   Used by AdminUserController::update. The self-lockout guard (admins may
 *   not modify themselves) lives in the controller, not here, because it
 *   depends on comparing the target ID to the authenticated admin.
 */
class UpdateUserByAdminRequest extends FormRequest
{
    /**
     * Permission was already enforced by the admin middleware; this request
     * only validates shape.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * - role: promote/demote between exactly 'owner' and 'admin'. No other
     *   roles exist in the system.
     * - status: 'inactive' tells the controller to soft-delete (deactivate)
     *   the account; 'active' restores a deactivated one. Using words instead
     *   of a boolean keeps intent obvious in request logs and payloads.
     *
     * Both fields are 'sometimes' so an admin can change just one at a time.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'role' => ['sometimes', 'required', 'string', Rule::in(['owner', 'admin'])],
            // "inactive" soft-deletes the account; "active" restores it.
            'status' => ['sometimes', 'required', 'string', Rule::in(['active', 'inactive'])],
        ];
    }
}
