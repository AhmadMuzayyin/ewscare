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
        Schema::table('riwayat_kesehatans', function (Blueprint $table) {
            $table->foreignId('dataset_training_id')->nullable()->after('kamar_id')->constrained()->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('riwayat_kesehatans', function (Blueprint $table) {
            $table->dropConstrainedForeignId('dataset_training_id');
        });
    }
};
