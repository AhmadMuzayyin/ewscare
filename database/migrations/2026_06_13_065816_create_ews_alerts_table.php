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
        Schema::create('ews_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kamar_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('wilayah')->nullable();
            $table->string('blok')->nullable();
            $table->foreignId('penyakit_id')->constrained()->cascadeOnDelete();
            $table->integer('jumlah_kasus');
            $table->enum('status', ['Aktif', 'Tertangani'])->default('Aktif');
            $table->date('tanggal_dideteksi')->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ews_alerts');
    }
};
