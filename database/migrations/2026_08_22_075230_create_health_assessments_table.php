<?php

/**
 * File: database/migrations/2026_08_22_075230_create_health_assessments_table.php
 *
 * Purpose:
 *   One row per diagnostic event: an owner submitted symptoms for a bird and
 *   the engine produced ranked results. Assessments are APPEND-ONLY — no
 *   update/delete endpoints exist anywhere. age/sex columns are snapshots
 *   taken at submission time so history stays accurate as birds grow or
 *   records change.
 *
 * NOTE on ordering: timestamp renamed (075230 < children 075231/075232)
 * because same-second migrations sort alphabetically, which had put child
 * tables before this parent and broken `php artisan migrate`.
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
        Schema::create('health_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gamefowl_id')->constrained()->restrictOnDelete();
            $table->string('age_at_assessment', 50)->nullable();
            $table->string('sex_at_assessment', 10)->nullable();
            $table->string('duration_of_symptoms', 30)->nullable();
            $table->string('appetite', 20)->nullable();
            $table->string('activity_level', 20)->nullable();
            $table->text('additional_notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('health_assessments');
    }
};
