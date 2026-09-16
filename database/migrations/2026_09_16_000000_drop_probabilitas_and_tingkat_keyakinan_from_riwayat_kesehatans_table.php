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
            $table->dropColumn(['probabilitas', 'tingkat_keyakinan']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('riwayat_kesehatans', function (Blueprint $table) {
            $table->double('probabilitas')->default(0)->after('tanggal_periksa');
            $table->double('tingkat_keyakinan')->default(0)->after('probabilitas');
        });
    }
};
