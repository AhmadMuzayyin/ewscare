<?php

namespace App\Exports;

use App\Models\Santri;
use Illuminate\Support\Enumerable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class SantriExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping
{
    public function __construct(private readonly bool $template = false)
    {
    }

    public function collection(): Enumerable
    {
        if ($this->template) {
            return collect([
                (object) [
                    'nis' => 'NIS0001',
                    'nama' => 'Contoh Nama Santri',
                    'gender' => 'L',
                    'kamar' => (object) [
                        'wilayah' => (object) ['nama_wilayah' => 'Contoh Wilayah 1'],
                        'blok' => 'Blok A',
                        'nama_kamar' => 'Kamar 01',
                    ],
                ],
            ]);
        }

        return Santri::with('kamar.wilayah')->orderBy('nama')->get();
    }

    public function headings(): array
    {
        return ['NIS', 'Nama', 'Gender', 'Wilayah', 'Blok', 'Nama Kamar'];
    }

    public function map($row): array
    {
        return [
            $row->nis,
            $row->nama,
            $row->gender,
            $row->kamar->wilayah->nama_wilayah ?? '',
            $row->kamar->blok ?? '',
            $row->kamar->nama_kamar ?? '',
        ];
    }
}
