<?php

/**
 * File: database/migrations/2026_08_22_054220_create_personal_access_tokens_table.php
 *
 * Purpose:
 *   Creates the table Laravel Sanctum uses to store API tokens. Every
 *   register/login response token corresponds to one row here; logout
 *   deletes its row, which is what invalidates the token. Published by
 *   Sanctum during Milestone 1 — do not modify its columns.
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
        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->morphs('tokenable');
            $table->text('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('personal_access_tokens');
    }
};
