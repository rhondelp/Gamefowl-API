<?php

/**
 * File: database/migrations/2026_08_22_075232_create_health_assessment_results_table.php
 *
 * Purpose:
 *   One row per ranked disease suggestion within an assessment: rank,
 *   match_score, JSON name lists for matched/missing symptoms, and snapshot
 *   columns (disease_name, severity_at_assessment, vet_warning_at_assessment)
 *   so historical results read correctly forever even if the knowledge base
 *   changes later. unique(health_assessment_id, rank) guarantees one result
 *   per rank position.
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
        Schema::create('health_assessment_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('health_assessment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('disease_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('rank');
            $table->unsignedTinyInteger('match_score');
            $table->json('matched_symptoms');
            $table->json('missing_symptoms');
            $table->string('disease_name', 255);
            $table->string('severity_at_assessment', 20);
            $table->text('vet_warning_at_assessment')->nullable();
            $table->timestamps();

            $table->unique(['health_assessment_id', 'rank']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('health_assessment_results');
    }
};
