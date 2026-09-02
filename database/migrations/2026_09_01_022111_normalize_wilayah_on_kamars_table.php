<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('kamars', function (Blueprint $table) {
            $table->foreignId('wilayah_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
        });

        if (Schema::hasColumn('kamars', 'wilayah')) {
            // Backfill: turn each distinct existing "wilayah" string into a wilayahs row,
            // then point every kamar at the matching wilayah_id.
            DB::table('kamars')->select('wilayah')->distinct()->get()->each(function ($row) {
                $wilayahId = DB::table('wilayahs')->where('nama_wilayah', $row->wilayah)->value('id');

                if (! $wilayahId) {
                    $wilayahId = DB::table('wilayahs')->insertGetId([
                        'nama_wilayah' => $row->wilayah,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                DB::table('kamars')->where('wilayah', $row->wilayah)->update(['wilayah_id' => $wilayahId]);
            });

            Schema::table('kamars', function (Blueprint $table) {
                $table->dropColumn('wilayah');
            });
        }

        Schema::table('kamars', function (Blueprint $table) {
            $table->foreignId('wilayah_id')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kamars', function (Blueprint $table) {
            $table->string('wilayah')->nullable()->after('id');
        });

        DB::table('kamars')->orderBy('id')->get()->each(function ($kamar) {
            $nama = DB::table('wilayahs')->where('id', $kamar->wilayah_id)->value('nama_wilayah');
            DB::table('kamars')->where('id', $kamar->id)->update(['wilayah' => $nama]);
        });

        Schema::table('kamars', function (Blueprint $table) {
            $table->string('wilayah')->nullable(false)->change();
            $table->dropConstrainedForeignId('wilayah_id');
        });
    }
};
