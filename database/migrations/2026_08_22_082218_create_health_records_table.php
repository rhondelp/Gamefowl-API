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
        Schema::create('health_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gamefowl_id')->constrained()->restrictOnDelete();
            $table->date('recorded_at');
            $table->string('type', 30);
            $table->string('title');
            $table->text('notes')->nullable();
            $table->decimal('weight', 5, 2)->nullable();
            $table->timestamps();

            $table->index(['gamefowl_id', 'recorded_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('health_records');
    }
};
