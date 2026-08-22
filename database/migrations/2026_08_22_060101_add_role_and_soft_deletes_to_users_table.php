<?php

/**
 * File: database/migrations/2026_08_22_060101_add_role_and_soft_deletes_to_users_table.php
 *
 * Purpose:
 *   Milestone 2 extension of the users table:
 *   - role: 'owner' or 'admin' (string, default 'owner'). Kept as a plain
 *     string rather than a Postgres native enum because enums are painful
 *     to extend later; validation lives in the app layer.
 *   - softDeletes: enables account deactivation (deleted_at) instead of
 *     hard deletion, preserving all bird/assessment history.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->default('owner')->after('password')->index();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role']);
            $table->dropSoftDeletes();
            $table->dropColumn('role');
        });
    }
};
