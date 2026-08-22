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
