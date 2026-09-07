<?php

namespace App\Imports;

use App\Models\Penyakit;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class PenyakitImport implements SkipsEmptyRows, SkipsOnFailure, ToModel, WithHeadingRow, WithValidation
{
    use SkipsFailures;

    public function model(array $row): Penyakit
    {
        return new Penyakit([
            'kode_penyakit' => trim((string) $row['kode_penyakit']),
            'nama_penyakit' => trim((string) $row['nama_penyakit']),
            'is_menular' => $this->parseBoolean($row['menular'] ?? null),
            'solusi_pencegahan' => isset($row['solusi_pencegahan']) && $row['solusi_pencegahan'] !== ''
                ? trim((string) $row['solusi_pencegahan'])
                : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'kode_penyakit' => 'required|string|max:50|unique:penyakits,kode_penyakit',
            'nama_penyakit' => 'required|string|max:255',
            'menular' => 'required|string',
            'solusi_pencegahan' => 'nullable|string',
        ];
    }

    private function parseBoolean(mixed $value): bool
    {
        return in_array(strtolower(trim((string) $value)), ['ya', 'yes', 'y', '1', 'true'], true);
    }
}
