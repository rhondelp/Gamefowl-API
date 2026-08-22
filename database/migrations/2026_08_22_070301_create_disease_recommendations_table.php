<?php

/**
 * File: database/migrations/2026_08_22_070301_create_disease_recommendations_table.php
 *
 * Purpose:
 *   Pivot linking diseases to their care-advice entries. unique(disease_id,
 *   recommendation_id) prevents duplicate links; cascade FKs keep the pivot
 *   consistent if a parent row were ever hard-deleted.
 *
 * NOTE on ordering: timestamp renamed (070301) so this runs after its parent
 * tables (see the rules migration for the full explanation).
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
        Schema::create('disease_recommendations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('disease_id')->constrained()->cascadeOnDelete();
            $table->foreignId('recommendation_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['disease_id', 'recommendation_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('disease_recommendations');
    }
};
