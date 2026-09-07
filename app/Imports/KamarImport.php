<?php

namespace App\Imports;

use App\Models\Kamar;
use App\Models\Wilayah;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class KamarImport implements SkipsEmptyRows, SkipsOnFailure, ToModel, WithHeadingRow, WithValidation
{
    use SkipsFailures;

    public function model(array $row): ?Kamar
    {
        $wilayah = Wilayah::where('nama_wilayah', trim((string) $row['wilayah']))->first();

        if (! $wilayah) {
            return null;
        }

        return new Kamar([
            'wilayah_id' => $wilayah->id,
            'blok' => trim((string) $row['blok']),
            'nama_kamar' => trim((string) $row['nama_kamar']),
            'kapasitas' => (int) $row['kapasitas'],
        ]);
    }

    public function rules(): array
    {
        return [
            'wilayah' => 'required|exists:wilayahs,nama_wilayah',
            'blok' => 'required|string|max:255',
            'nama_kamar' => 'required|string|max:255',
            'kapasitas' => 'required|integer|min:1',
        ];
    }
}
