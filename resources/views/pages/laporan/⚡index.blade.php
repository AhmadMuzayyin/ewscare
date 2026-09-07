<?php

use App\Models\RiwayatKesehatan;
use App\Models\Penyakit;
use App\Models\Kamar;
use App\Models\Wilayah;
use App\Models\DatasetTraining;
use App\Models\Gejala;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Title;
use Livewire\Attributes\Computed;

new #[Title('Laporan Riwayat Kesehatan')] class extends Component {
    use WithPagination;

    public string $search = '';
    public string $filterPenyakit = '';
    public string $filterWilayah = '';
    public string $filterBlok = '';
    public string $startDate = '';
    public string $endDate = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilterPenyakit(): void
    {
        $this->resetPage();
    }

    public function updatedFilterWilayah(): void
    {
        $this->resetPage();
    }

    public function updatedFilterBlok(): void
    {
        $this->resetPage();
    }

    public function updatedStartDate(): void
    {
        $this->resetPage();
    }

    public function updatedEndDate(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function riwayats()
    {
        $paginator = RiwayatKesehatan::query()
            ->with(['santri.kamar.wilayah', 'kamar.wilayah', 'penyakit', 'gejalas'])
            ->when($this->search, function ($query) {
                $query->whereHas('santri', function ($q) {
                    $q->where('nama', 'like', '%' . $this->search . '%')
                        ->orWhere('nis', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->filterPenyakit, fn($query) => $query->where('penyakit_id', $this->filterPenyakit))
            ->when($this->filterWilayah, function ($query) {
                $query->whereHas('kamar', fn($q) => $q->where('wilayah_id', $this->filterWilayah));
            })
            ->when($this->filterBlok, function ($query) {
                $query->whereHas('kamar', fn($q) => $q->where('blok', $this->filterBlok));
            })
            ->when($this->startDate, fn($query) => $query->whereDate('tanggal_periksa', '>=', $this->startDate))
            ->when($this->endDate, fn($query) => $query->whereDate('tanggal_periksa', '<=', $this->endDate))
            ->orderBy('tanggal_periksa', 'desc')
            ->paginate(15);

        // Attach the model's current top prediction to each row, so staff can see whether
        // the recorded diagnosis still matches what Naive Bayes would classify today.
        $paginator->getCollection()->transform(function ($riwayat) {
            $prediction = $this->classifyGejala($riwayat->gejalas->pluck('id')->toArray());

            $riwayat->prediksi_penyakit = $prediction['penyakit'] ?? null;
            $riwayat->prediksi_confidence = $prediction ? $prediction['probability'] * 100 : null;
            $riwayat->prediksi_cocok = $prediction ? $prediction['penyakit']->id === $riwayat->penyakit_id : null;

            return $riwayat;
        });

        return $paginator;
    }

    /**
     * Naive Bayes classification (with Laplace smoothing) of the given gejala against
     * Dataset Training, normalized across all penyakit. Returns the top-scoring result,
     * or null when there's no Dataset Training to classify against yet.
     */
    private function classifyGejala(array $checkedGejalaIds): ?array
    {
        $totalDatasets = DatasetTraining::count();
        $penyakits = Penyakit::all();

        if ($totalDatasets === 0 || $penyakits->isEmpty()) {
            return null;
        }

        $gejalas = Gejala::all();
        $results = [];

        foreach ($penyakits as $penyakit) {
            $countPenyakit = DatasetTraining::where('penyakit_id', $penyakit->id)->count();
            $prior = ($countPenyakit + 1) / ($totalDatasets + $penyakits->count());

            $likelihood = 1.0;

            foreach ($gejalas as $gejala) {
                $countSymptomWithDisease = DatasetTraining::where('penyakit_id', $penyakit->id)
                    ->whereHas('gejalas', fn($q) => $q->where('gejala_id', $gejala->id))
                    ->count();

                $pSymptomPresent = ($countSymptomWithDisease + 1) / ($countPenyakit + 2);

                $likelihood *= in_array($gejala->id, $checkedGejalaIds) ? $pSymptomPresent : (1.0 - $pSymptomPresent);
            }

            $results[$penyakit->id] = ['penyakit' => $penyakit, 'score' => $prior * $likelihood];
        }

        $totalScore = array_sum(array_column($results, 'score'));

        foreach ($results as $id => $data) {
            $results[$id]['probability'] = $totalScore > 0 ? $data['score'] / $totalScore : 1 / $penyakits->count();
        }

        uasort($results, fn($a, $b) => $b['probability'] <=> $a['probability']);

        return reset($results);
    }

    #[Computed]
    public function penyakitOptions()
    {
        return Penyakit::orderBy('nama_penyakit')->get();
    }

    #[Computed]
    public function wilayahOptions()
    {
        return Wilayah::orderBy('nama_wilayah')->get();
    }

    #[Computed]
    public function blokOptions(): array
    {
        return Kamar::distinct()->pluck('blok')->filter()->values()->toArray();
    }
};
?>

<div class="flex h-full w-full flex-1 flex-col gap-6 p-6">
    <div>
        <flux:heading size="xl" level="1">{{ __('Laporan Riwayat Kesehatan') }}</flux:heading>
        <flux:text>{{ __('Rekapitulasi dan pelaporan historis data pemeriksaan kesehatan santri.') }}</flux:text>
    </div>

    <!-- Filters Section -->
    <flux:card class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4">
        <flux:input label="{{ __('Cari Santri') }}" wire:model.live="search" placeholder="Nama / NIS..." icon="magnifying-glass" />

        <flux:select label="{{ __('Penyakit') }}" wire:model.live="filterPenyakit" placeholder="Semua Penyakit">
            <option value="">Semua Penyakit</option>
            @foreach ($this->penyakitOptions as $option)
            <option value="{{ $option->id }}">{{ $option->nama_penyakit }}</option>
            @endforeach
        </flux:select>

        <flux:select label="{{ __('Wilayah') }}" wire:model.live="filterWilayah" placeholder="Semua Wilayah">
            <option value="">Semua Wilayah</option>
            @foreach ($this->wilayahOptions as $option)
            <option value="{{ $option->id }}">{{ $option->nama_wilayah }}</option>
            @endforeach
        </flux:select>

        <flux:select label="{{ __('Blok') }}" wire:model.live="filterBlok" placeholder="Semua Blok">
            <option value="">Semua Blok</option>
            @foreach ($this->blokOptions as $option)
            <option value="{{ $option }}">{{ $option }}</option>
            @endforeach
        </flux:select>

        <flux:input type="date" label="{{ __('Mulai Tanggal') }}" wire:model.live="startDate" />
        <flux:input type="date" label="{{ __('Hingga Tanggal') }}" wire:model.live="endDate" />
    </flux:card>

    <!-- Table Section -->
    <flux:card class="overflow-x-auto p-0">
        <flux:table :paginate="$this->riwayats">
            <flux:table.columns>
                <flux:table.column class="pl-4">{{ __('Tanggal') }}</flux:table.column>
                <flux:table.column>{{ __('Santri') }}</flux:table.column>
                <flux:table.column>{{ __('Kamar/Gedung') }}</flux:table.column>
                <flux:table.column>{{ __('Gejala Klinis') }}</flux:table.column>
                <flux:table.column>{{ __('Klasifikasi Penyakit') }}</flux:table.column>
                <flux:table.column>{{ __('Keyakinan') }}</flux:table.column>
                <flux:table.column>{{ __('Prediksi Model') }}</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @foreach ($this->riwayats as $riwayat)
                <flux:table.row :key="$riwayat->id">
                    <flux:table.cell class="pl-4 whitespace-nowrap">{{ \Carbon\Carbon::parse($riwayat->tanggal_periksa)->translatedFormat('d M Y') }}</flux:table.cell>
                    <flux:table.cell>
                        <div class="font-medium text-zinc-900 dark:text-white">{{ $riwayat->santri->nama }}</div>
                        <div class="text-xs text-zinc-500">{{ $riwayat->santri->nis }}</div>
                    </flux:table.cell>
                    <flux:table.cell>
                        @if($riwayat->kamar)
                        <div class="text-sm">{{ $riwayat->kamar->wilayah->nama_wilayah }}</div>
                        <div class="text-xs text-zinc-500">{{ $riwayat->kamar->blok }} - {{ $riwayat->kamar->nama_kamar }}</div>
                        @else
                        <span class="text-zinc-400">—</span>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell class="max-w-xs">
                        <div class="flex flex-wrap gap-1">
                            @foreach ($riwayat->gejalas as $gejala)
                            <flux:badge size="sm" variant="outline" color="blue">
                                {{ $gejala->nama_gejala }}
                            </flux:badge>
                            @endforeach
                        </div>
                    </flux:table.cell>
                    <flux:table.cell variant="strong">
                        <div class="flex items-center gap-2">
                            <span>{{ $riwayat->penyakit->nama_penyakit }}</span>
                            <flux:badge size="sm" :color="$riwayat->penyakit->is_menular ? 'red' : 'zinc'">
                                {{ $riwayat->penyakit->is_menular ? __('Menular') : __('Tidak Menular') }}
                            </flux:badge>
                        </div>
                    </flux:table.cell>
                    <flux:table.cell>
                        <flux:badge color="blue" variant="outline">{{ number_format($riwayat->tingkat_keyakinan, 2) }}%</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>
                        @if($riwayat->prediksi_penyakit)
                            <div class="flex items-center gap-2">
                                <span class="text-sm">{{ $riwayat->prediksi_penyakit->nama_penyakit }}</span>
                                @if($riwayat->prediksi_cocok)
                                    <flux:badge color="emerald" size="sm">{{ __('Cocok') }}</flux:badge>
                                @else
                                    <flux:badge color="red" size="sm">{{ __('Tidak Cocok') }}</flux:badge>
                                @endif
                            </div>
                        @else
                            <flux:text size="sm" class="text-zinc-400">—</flux:text>
                        @endif
                    </flux:table.cell>
                </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    </flux:card>
</div>