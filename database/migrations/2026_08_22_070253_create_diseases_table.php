<?php

/**
 * File: database/migrations/2026_08_22_070253_create_diseases_table.php
 *
 * Purpose:
 *   Knowledge base: conditions the expert system can suggest. Carries all
 *   owner-facing educational content plus vet_warning, which only surfaces
 *   for severe/critical severities. name is unique; is_active follows the
 *   project-wide deactivate-don't-delete convention.
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
        Schema::create('diseases', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('description');
            $table->string('severity', 20);
            $table->text('general_info')->nullable();
            $table->text('recommended_action');
            $table->text('prevention_info')->nullable();
            $table->text('vet_warning')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('diseases');
    }
};
