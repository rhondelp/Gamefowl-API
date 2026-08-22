<?php

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
