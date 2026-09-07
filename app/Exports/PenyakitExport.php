<?php

namespace App\Exports;

use App\Models\Penyakit;
use Illuminate\Support\Enumerable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class PenyakitExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping
{
    public function __construct(private readonly bool $template = false)
    {
    }

    public function collection(): Enumerable
    {
        if ($this->template) {
            return collect([
                (object) [
                    'kode_penyakit' => 'P01',
                    'nama_penyakit' => 'Contoh Penyakit',
                    'is_menular' => true,
                    'solusi_pencegahan' => 'Contoh rekomendasi tindakan preventif.',
                ],
            ]);
        }

        return Penyakit::orderBy('nama_penyakit')->get();
    }

    public function headings(): array
    {
        return ['Kode Penyakit', 'Nama Penyakit', 'Menular', 'Solusi Pencegahan'];
    }

    public function map($row): array
    {
        return [
            $row->kode_penyakit,
            $row->nama_penyakit,
            $row->is_menular ? 'Ya' : 'Tidak',
            $row->solusi_pencegahan,
        ];
    }
}
