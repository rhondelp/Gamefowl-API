<?php

/**
 * File: app/Models/User.php
 *
 * Purpose:
 *   Represents an application account (a gamefowl owner or an admin).
 *   This model is the center of authentication: Laravel Sanctum reads it
 *   during login, issues API tokens for it, and resolves it for every
 *   request that passes the `auth:sanctum` middleware.
 *
 * How it fits into the project:
 *   - AuthController (register/login/logout/me) creates and authenticates Users.
 *   - Every Gamefowl belongs to exactly one User; all ownership checks in
 *     GamefowlPolicy compare `$gamefowl->user_id` against the authenticated User.
 *   - AdminUserController manages User accounts (promote/demote/deactivate).
 *   - The `role` column ('owner' or 'admin') powers the `admin` middleware alias.
 *
 * Notes for new developers:
 *   - SoftDeletes means "deleting" a user only stamps `deleted_at`
 *     (account deactivation); the row stays so historical health data
 *     keeps working. Admins restore accounts by calling `restore()`.
 *   - The `password` cast to 'hashed' tells Eloquent to hash plain-text
 *     passwords automatically whenever one is set — never call Hash::make
 *     twice on assignment.
 */

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /**
     * Traits this model uses:
     * - HasApiTokens: required by Sanctum, enables createToken()/tokens()
     *   used by AuthController to hand out bearer tokens.
     * - HasFactory: lets tests build users via UserFactory.
     * - Notifiable: standard Laravel notification support (currently unused).
     * - SoftDeletes: makes delete() stamp deleted_at instead of removing
     *   the row, and adds trashed-account awareness for admin listings.
     */
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    /**
     * Fields that may be filled through mass assignment (create/fill/update).
     * Everything NOT listed here is ignored on purpose — this is what stops
     * a caller from sneaking in unexpected columns. Note that `role` IS
     * fillable because admins legitimately change it, but registration never
     * takes it from user input (AuthController hardcodes 'owner').
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    /**
     * Fields hidden whenever the model is turned into JSON/an array.
     * This guarantees password hashes and remember tokens can never leak
     * through any API Resource (UserResource builds on top of this).
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Automatic type conversions applied when attributes are read/written.
     * - email_verified_at: stored as a raw DB timestamp, exposed as a Carbon date.
     * - password: hashing happens automatically on assignment (see class note).
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Whether this account holds the admin role.
     *
     * Used by App\Http\Middleware\EnsureUserIsAdmin to gate every
     * /api/v1/admin/* route, and by EnsureUserIsAdmin's Forbidden response.
     * Kept as a tiny helper so the string comparison lives in ONE place —
     * if role names ever change, only this file needs updating.
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * One-to-many: every gamefowl profile this account owns.
     *
     * Used by owner-facing controllers (GamefowlController,
     * HealthAssessmentController, HealthRecordController,
     * HealthHistoryController) to scope queries — e.g.
     * `$request->user()->gamefowls()->findOrFail($id)` is the pattern that
     * guarantees an owner can only ever touch their own birds.
     */
    public function gamefowls(): HasMany
    {
        return $this->hasMany(Gamefowl::class);
    }

    /**
     * All assessments across ALL of this user's birds, reached through the
     * gamefowls table (a "hasManyThrough": User -> Gamefowl -> HealthAssessment).
     *
     * Used by AdminUserController::show with withCount() to display each
     * account's total assessment count without loading the actual rows.
     */
    public function healthAssessments(): HasManyThrough
    {
        return $this->hasManyThrough(HealthAssessment::class, Gamefowl::class);
    }
}
