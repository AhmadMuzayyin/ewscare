<?php

namespace App\Exports;

use App\Models\Gejala;
use Illuminate\Support\Enumerable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class GejalaExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping
{
    public function __construct(private readonly bool $template = false)
    {
    }

    public function collection(): Enumerable
    {
        if ($this->template) {
            return collect([
                (object) ['kode_gejala' => 'G01', 'nama_gejala' => 'Contoh Gejala'],
            ]);
        }

        return Gejala::orderBy('kode_gejala')->get();
    }

    public function headings(): array
    {
        return ['Kode Gejala', 'Nama Gejala'];
    }

    public function map($row): array
    {
        return [$row->kode_gejala, $row->nama_gejala];
    }
}
