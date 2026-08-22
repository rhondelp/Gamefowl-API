<?php

/**
 * File: database/migrations/2026_08_22_070253_create_symptoms_table.php
 *
 * Purpose:
 *   Knowledge base: the signs owners can report ("Bloody droppings", ...).
 *   - name is unique (duplicate checklist entries would be ambiguous).
 *   - category is a free-form grouping label used by ?grouped=1 listings.
 *   - is_active: deactivated symptoms disappear from owners and from engine
 *     scoring, but historical assessment snapshots remain intact.
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
        Schema::create('symptoms', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->string('category', 100);
            $table->string('severity', 20);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('symptoms');
    }
};
