<?php

namespace Database\Seeders;

use App\Models\DatasetTraining;
use App\Models\Gejala;
use App\Models\Kamar;
use App\Models\Penyakit;
use App\Models\Santri;
use App\Models\User;
use App\Models\Wilayah;
use Faker\Factory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Administrator',
            'email' => 'admin@admin.com',
        ]);

        $kamars = [
            // Wilayah 1
            ['wilayah' => 'Wilayah 1', 'blok' => 'Blok A', 'jumlah' => 9],
            ['wilayah' => 'Wilayah 1', 'blok' => 'Blok B', 'jumlah' => 12],
            ['wilayah' => 'Wilayah 1', 'blok' => 'DKL', 'jumlah' => 2],
            ['wilayah' => 'Wilayah 1', 'blok' => 'BPBAE', 'jumlah' => 3],

            // Wilayah 2
            ['wilayah' => 'Wilayah 2', 'blok' => 'Blok C', 'jumlah' => 9],
            ['wilayah' => 'Wilayah 2', 'blok' => 'Blok D', 'jumlah' => 9],
            ['wilayah' => 'Wilayah 2', 'blok' => 'Blok E', 'jumlah' => 12],
            ['wilayah' => 'Wilayah 2', 'blok' => 'DKL', 'jumlah' => 4],
            ['wilayah' => 'Wilayah 2', 'blok' => 'BPBAE', 'jumlah' => 2],

            // Wilayah 3
            ['wilayah' => 'Wilayah 3', 'blok' => 'Blok F', 'jumlah' => 11],
            ['wilayah' => 'Wilayah 3', 'blok' => 'B. Arab', 'jumlah' => 4],
            ['wilayah' => 'Wilayah 3', 'blok' => 'JQL', 'jumlah' => 1],
            ['wilayah' => 'Wilayah 3', 'blok' => 'NTQ', 'jumlah' => 3],
        ];

        $wilayahModels = [];
        foreach (array_unique(array_column($kamars, 'wilayah')) as $namaWilayah) {
            $wilayahModels[$namaWilayah] = Wilayah::create(['nama_wilayah' => $namaWilayah]);
        }

        foreach ($kamars as $group) {
            for ($i = 1; $i <= $group['jumlah']; $i++) {
                Kamar::create([
                    'wilayah_id' => $wilayahModels[$group['wilayah']]->id,
                    'blok' => $group['blok'],
                    'nama_kamar' => 'Kamar '.str_pad((string) $i, 2, '0', STR_PAD_LEFT),
                    'kapasitas' => 15, // Ditingkatkan untuk menampung 10 santri
                ]);
            }
        }

        // 1. Seed Gejala
        $gejalasData = [
            ['kode_gejala' => 'G01', 'nama_gejala' => 'Demam / Suhu Badan Tinggi'],
            ['kode_gejala' => 'G02', 'nama_gejala' => 'Batuk Kering / Berdahak'],
            ['kode_gejala' => 'G03', 'nama_gejala' => 'Pilek / Hidung Tersumbat / Bersin'],
            ['kode_gejala' => 'G04', 'nama_gejala' => 'Gatal-gatal di Area Kulit / Sela Jari'],
            ['kode_gejala' => 'G05', 'nama_gejala' => 'Bintik Merah / Ruam / Kemerahan di Kulit'],
            ['kode_gejala' => 'G06', 'nama_gejala' => 'Sakit Tenggorokan / Nyeri Menelan'],
            ['kode_gejala' => 'G07', 'nama_gejala' => 'Diare / Mencret / Sakit Perut Melilit'],
            ['kode_gejala' => 'G08', 'nama_gejala' => 'Pusing / Sakit Kepala'],
            ['kode_gejala' => 'G09', 'nama_gejala' => 'Sesak Napas / Nyeri Dada'],
            ['kode_gejala' => 'G10', 'nama_gejala' => 'Mual / Muntah / Nafsu Makan Menurun'],
        ];

        $gejalaModels = [];
        foreach ($gejalasData as $g) {
            $gejalaModels[$g['kode_gejala']] = Gejala::create($g);
        }

        // 2. Seed Penyakit
        $penyakitsData = [
            [
                'kode_penyakit' => 'P01',
                'nama_penyakit' => 'Influenza / Flu',
                'is_menular' => true,
                'solusi_pencegahan' => 'Isolasi mandiri di kamar khusus, gunakan masker medis, konsumsi air hangat, dan berikan vitamin penambah imun.',
            ],
            [
                'kode_penyakit' => 'P02',
                'nama_penyakit' => 'Scabies / Kudisan',
                'is_menular' => true,
                'solusi_pencegahan' => 'Rebus semua sprei, pakaian, dan handuk dengan air panas, jemur kasur di bawah terik matahari secara berkala, pisahkan tempat tidur.',
            ],
            [
                'kode_penyakit' => 'P03',
                'nama_penyakit' => 'Gastroenteritis / Diare',
                'is_menular' => true,
                'solusi_pencegahan' => 'Pastikan kebersihan air minum pondok, cuci tangan dengan sabun sebelum makan, dan segera berikan larutan oralit.',
            ],
            [
                'kode_penyakit' => 'P04',
                'nama_penyakit' => 'Demam Berdarah Dengue (DBD)',
                'is_menular' => false,
                'solusi_pencegahan' => 'Lakukan gerakan 3M Plus (Menguras, Menutup, Mendaur ulang), lakukan fogging di sekitar wilayah blok kamar, gunakan lotion anti-nyamuk.',
            ],
            [
                'kode_penyakit' => 'P05',
                'nama_penyakit' => 'Faringitis / Radang Tenggorokan',
                'is_menular' => false,
                'solusi_pencegahan' => 'Istirahat yang cukup, hindari konsumsi makanan berminyak / gorengan dan minuman es dingin.',
            ],
        ];

        $penyakitModels = [];
        foreach ($penyakitsData as $p) {
            $penyakitModels[$p['kode_penyakit']] = Penyakit::create($p);
        }

        // 3. Seed Dataset Training (Kombinasi Gejala & Penyakit)
        $datasets = [
            // Influenza (P01)
            ['penyakit' => 'P01', 'gejalas' => ['G01', 'G02', 'G03']],
            ['penyakit' => 'P01', 'gejalas' => ['G01', 'G02', 'G03', 'G08']],
            ['penyakit' => 'P01', 'gejalas' => ['G01', 'G03', 'G06', 'G08']],
            ['penyakit' => 'P01', 'gejalas' => ['G02', 'G03', 'G06']],
            ['penyakit' => 'P01', 'gejalas' => ['G01', 'G02', 'G08']],

            // Scabies (P02)
            ['penyakit' => 'P02', 'gejalas' => ['G04', 'G05']],
            ['penyakit' => 'P02', 'gejalas' => ['G04']],
            ['penyakit' => 'P02', 'gejalas' => ['G04', 'G05']],

            // Gastroenteritis (P03)
            ['penyakit' => 'P03', 'gejalas' => ['G07', 'G10']],
            ['penyakit' => 'P03', 'gejalas' => ['G07']],
            ['penyakit' => 'P03', 'gejalas' => ['G07', 'G08', 'G10']],

            // DBD (P04)
            ['penyakit' => 'P04', 'gejalas' => ['G01', 'G05', 'G08', 'G10']],
            ['penyakit' => 'P04', 'gejalas' => ['G01', 'G08', 'G10']],
            ['penyakit' => 'P04', 'gejalas' => ['G01', 'G05', 'G08']],

            // Faringitis (P05)
            ['penyakit' => 'P05', 'gejalas' => ['G02', 'G06']],
            ['penyakit' => 'P05', 'gejalas' => ['G01', 'G06']],
            ['penyakit' => 'P05', 'gejalas' => ['G02', 'G06', 'G08']],
        ];

        foreach ($datasets as $data) {
            $penyakit = $penyakitModels[$data['penyakit']];
            $dataset = DatasetTraining::create([
                'penyakit_id' => $penyakit->id,
            ]);

            $gejalaIds = [];
            foreach ($data['gejalas'] as $gCode) {
                $gejalaIds[] = $gejalaModels[$gCode]->id;
            }
            $dataset->gejalas()->sync($gejalaIds);
        }

        // 4. Seed Santri (10 santri per kamar menggunakan Faker)
        $faker = Factory::create('id_ID');
        $allKamars = Kamar::all();

        foreach ($allKamars as $kamar) {
            for ($i = 0; $i < 10; $i++) {
                Santri::create([
                    'kamar_id' => $kamar->id,
                    'nis' => 'NIS'.$kamar->id.str_pad((string) $i, 2, '0', STR_PAD_LEFT).rand(10, 99),
                    'nama' => $faker->name,
                    'gender' => $faker->randomElement(['L', 'P']),
                ]);
            }
        }
    }
}
