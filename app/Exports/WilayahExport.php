<?php

namespace App\Exports;

use App\Models\Wilayah;
use Illuminate\Support\Enumerable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class WilayahExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping
{
    /**
     * @param  bool  $template  When true, returns one example row instead of real data
     *                          (used to generate the downloadable import template).
     */
    public function __construct(private readonly bool $template = false)
    {
    }

    public function collection(): Enumerable
    {
        if ($this->template) {
            return collect([
                (object) ['nama_wilayah' => 'Contoh Wilayah 1'],
            ]);
        }

        return Wilayah::orderBy('nama_wilayah')->get();
    }

    public function headings(): array
    {
        return ['Nama Wilayah'];
    }

    public function map($row): array
    {
        return [$row->nama_wilayah];
    }
}
