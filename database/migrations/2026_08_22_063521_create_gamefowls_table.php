<?php

/**
 * File: database/migrations/2026_08_22_063521_create_gamefowls_table.php
 *
 * Purpose:
 *   Creates the birds themselves. Key decisions baked into this schema:
 *   - user_id FK uses RESTRICT on delete: a bird's history must never be
 *     silently destroyed by deleting its owner.
 *   - date_of_birth stored instead of age so age never goes stale.
 *   - BOTH is_active AND softDeletes exist on purpose: is_active is an
 *     owner-facing status toggle ("retired"), soft-delete protects against
 *     accidental removal while keeping all health data queryable.
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
        Schema::create('gamefowls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('breed')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('sex', 10)->default('unknown');
            $table->string('color')->nullable();
            $table->decimal('weight', 5, 2)->nullable();
            $table->date('date_acquired')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gamefowls');
    }
};
