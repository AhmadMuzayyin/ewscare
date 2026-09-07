<?php

namespace App\Imports;

use App\Models\Gejala;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class GejalaImport implements SkipsEmptyRows, SkipsOnFailure, ToModel, WithHeadingRow, WithValidation
{
    use SkipsFailures;

    public function model(array $row): Gejala
    {
        return new Gejala([
            'kode_gejala' => trim((string) $row['kode_gejala']),
            'nama_gejala' => trim((string) $row['nama_gejala']),
        ]);
    }

    public function rules(): array
    {
        return [
            'kode_gejala' => 'required|string|max:50|unique:gejalas,kode_gejala',
            'nama_gejala' => 'required|string|max:255',
        ];
    }
}
