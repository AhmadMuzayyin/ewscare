<?php

namespace App\Imports;

use App\Models\Kamar;
use App\Models\Santri;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class SantriImport implements SkipsEmptyRows, SkipsOnFailure, ToModel, WithHeadingRow, WithValidation
{
    use SkipsFailures;

    public function model(array $row): ?Santri
    {
        $kamar = Kamar::whereHas('wilayah', fn ($q) => $q->where('nama_wilayah', trim((string) $row['wilayah'])))
            ->where('blok', trim((string) $row['blok']))
            ->where('nama_kamar', trim((string) $row['nama_kamar']))
            ->first();

        if (! $kamar) {
            return null;
        }

        return new Santri([
            'nis' => trim((string) $row['nis']),
            'nama' => trim((string) $row['nama']),
            'gender' => strtoupper(trim((string) $row['gender'])),
            'kamar_id' => $kamar->id,
        ]);
    }

    public function rules(): array
    {
        return [
            'nis' => 'required|string|max:50|unique:santris,nis',
            'nama' => 'required|string|max:255',
            'gender' => 'required|in:L,P,l,p',
            'wilayah' => 'required|exists:wilayahs,nama_wilayah',
            'blok' => 'required|string',
            'nama_kamar' => 'required|string',
        ];
    }
}
