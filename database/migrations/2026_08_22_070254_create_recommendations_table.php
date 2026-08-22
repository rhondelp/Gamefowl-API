<?php

/**
 * File: database/migrations/2026_08_22_070254_create_recommendations_table.php
 *
 * Purpose:
 *   Knowledge base: care-advice entries ("Isolate affected birds", ...) that
 *   admins attach to diseases. category uses a constrained value list defined
 *   in the Recommendation model so app-side grouping stays predictable.
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
        Schema::create('recommendations', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('content');
            $table->string('category', 100);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recommendations');
    }
};
