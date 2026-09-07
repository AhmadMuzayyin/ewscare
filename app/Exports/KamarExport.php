<?php

namespace App\Exports;

use App\Models\Kamar;
use App\Models\Wilayah;
use Illuminate\Support\Enumerable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class KamarExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping
{
    public function __construct(private readonly bool $template = false)
    {
    }

    public function collection(): Enumerable
    {
        if ($this->template) {
            return collect([
                (object) [
                    'wilayah' => (object) [
                        'nama_wilayah' => Wilayah::query()->value('nama_wilayah') ?? 'Contoh Wilayah 1',
                    ],
                    'blok' => 'Blok A',
                    'nama_kamar' => 'Kamar 01',
                    'kapasitas' => 10,
                ],
            ]);
        }

        return Kamar::with('wilayah')->orderBy('wilayah_id')->orderBy('blok')->orderBy('nama_kamar')->get();
    }

    public function headings(): array
    {
        return ['Wilayah', 'Blok', 'Nama Kamar', 'Kapasitas'];
    }

    public function map($row): array
    {
        return [
            $row->wilayah->nama_wilayah,
            $row->blok,
            $row->nama_kamar,
            $row->kapasitas,
        ];
    }
}
