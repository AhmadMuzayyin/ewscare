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
        Schema::create('riwayat_kesehatans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('santri_id')->constrained()->cascadeOnDelete();
            $table->foreignId('penyakit_id')->constrained()->cascadeOnDelete();
            $table->foreignId('kamar_id')->constrained()->cascadeOnDelete(); // Denormalisasi lokasi kamar saat diperiksa
            $table->date('tanggal_periksa')->index();
            $table->double('probabilitas');
            $table->double('tingkat_keyakinan');
            $table->timestamps();
        });

        Schema::create('gejala_riwayat_kesehatan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('riwayat_kesehatan_id')->constrained('riwayat_kesehatans')->cascadeOnDelete();
            $table->foreignId('gejala_id')->constrained('gejalas')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['riwayat_kesehatan_id', 'gejala_id'], 'gejala_riwayat_kesehatan_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gejala_riwayat_kesehatan');
        Schema::dropIfExists('riwayat_kesehatans');
    }
};
