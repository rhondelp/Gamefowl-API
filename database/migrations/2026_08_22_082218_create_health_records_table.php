<?php

/**
 * File: database/migrations/2026_08_22_082218_create_health_records_table.php
 *
 * Purpose:
 *   Manual, human-entered logbook entries for a bird (vet visits, weight
 *   checks, vaccinations, notes) — separate from engine-generated
 *   assessments because the author, lifecycle, and shape differ.
 *   recorded_at is owner-chosen so backdating is possible; composite index
 *   on (gamefowl_id, recorded_at) keeps timeline sorting fast.
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
        Schema::create('health_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gamefowl_id')->constrained()->restrictOnDelete();
            $table->date('recorded_at');
            $table->string('type', 30);
            $table->string('title');
            $table->text('notes')->nullable();
            $table->decimal('weight', 5, 2)->nullable();
            $table->timestamps();

            $table->index(['gamefowl_id', 'recorded_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('health_records');
    }
};
