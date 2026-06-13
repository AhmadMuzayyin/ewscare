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
        Schema::create('dataset_trainings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('penyakit_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('dataset_training_gejala', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dataset_training_id')->constrained('dataset_trainings')->cascadeOnDelete();
            $table->foreignId('gejala_id')->constrained('gejalas')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['dataset_training_id', 'gejala_id'], 'dataset_training_gejala_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dataset_training_gejala');
        Schema::dropIfExists('dataset_trainings');
    }
};
