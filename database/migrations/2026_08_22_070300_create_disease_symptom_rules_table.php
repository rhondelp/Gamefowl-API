<?php

/**
 * File: database/migrations/2026_08_22_070300_create_disease_symptom_rules_table.php
 *
 * Purpose:
 *   THE knowledge base itself: one row per (disease, symptom) connection,
 *   with a weight (1-5) saying how strongly that symptom indicates that
 *   disease. DiagnosticEngine sums these weights to compute match scores.
 *
 * Constraints:
 * - unique(disease_id, symptom_id): a pair can only exist once.
 * - cascadeOnDelete FKs: if a disease/symptom row were ever hard-deleted at
 *   DB level its rules go too — though no API endpoint ever hard-deletes.
 *
 * NOTE on ordering: this file's timestamp was renamed (070300) because child
 * tables must run AFTER their parents; same-second alphabetical sorting had
 * put it before create_diseases_table and broken `php artisan migrate`.
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
        Schema::create('disease_symptom_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('disease_id')->constrained()->cascadeOnDelete();
            $table->foreignId('symptom_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('weight');
            $table->timestamps();

            $table->unique(['disease_id', 'symptom_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('disease_symptom_rules');
    }
};
