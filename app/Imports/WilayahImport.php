<?php

namespace App\Imports;

use App\Models\Wilayah;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class WilayahImport implements SkipsEmptyRows, SkipsOnFailure, ToModel, WithHeadingRow, WithValidation
{
    use SkipsFailures;

    public function model(array $row): Wilayah
    {
        return new Wilayah([
            'nama_wilayah' => trim((string) $row['nama_wilayah']),
        ]);
    }

    public function rules(): array
    {
        return [
            'nama_wilayah' => 'required|string|max:255|unique:wilayahs,nama_wilayah',
        ];
    }
}
