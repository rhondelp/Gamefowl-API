<?php

/**
 * File: database/migrations/2026_08_22_075231_create_health_assessment_symptoms_table.php
 *
 * Purpose:
 *   Pivot: which symptoms the owner selected for an assessment, with a
 *   SNAPSHOT of each symptom's name at submission time (symptom_name).
 *   The snapshot is deliberate denormalization — renames/deactivations in
 *   the knowledge base must never rewrite what an owner reported historically.
 *
 * NOTE on ordering: runs after create_health_assessments_table.
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
        Schema::create('health_assessment_symptoms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('health_assessment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('symptom_id')->constrained()->restrictOnDelete();
            $table->string('symptom_name');
            $table->timestamps();

            $table->unique(['health_assessment_id', 'symptom_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('health_assessment_symptoms');
    }
};
