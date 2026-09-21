<?php

/**
 * File: database/migrations/2026_09_21_111516_add_recommendations_to_health_assessment_results_table.php
 *
 * Purpose:
 *   Adds a `recommendations` JSON snapshot to each ranked result: the care
 *   advice (id, title, content, category) linked to that disease and active
 *   at submission time. Same immutable-snapshot rule as disease_name /
 *   severity_at_assessment / vet_warning_at_assessment, so later admin
 *   edits, deactivations, or unlinks never change what a past assessment
 *   shows.
 *
 *   Nullable on purpose: rows written before this column existed never
 *   captured their advice, and they are NOT backfilled — copying today's
 *   knowledge base into them would present current advice as if it had
 *   been recorded that day. NULL means "not recorded"; an empty array means
 *   "recorded, but the disease had no active recommendations linked".
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
        Schema::table('health_assessment_results', function (Blueprint $table) {
            $table->json('recommendations')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('health_assessment_results', function (Blueprint $table) {
            $table->dropColumn('recommendations');
        });
    }
};
