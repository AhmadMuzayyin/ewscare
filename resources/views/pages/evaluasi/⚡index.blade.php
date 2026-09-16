<?php

use App\Models\DatasetTraining;
use App\Models\Gejala;
use App\Models\Penyakit;
use App\Models\RiwayatKesehatan;
use App\Models\Santri;
use Livewire\Component;
use Livewire\Attributes\Title;
use Livewire\Attributes\Computed;
use Flux\Flux;

new #[Title('Klasifikasi & Pengujian Model')] class extends Component {
    public ?int $santri_id = null;

    // Array to store the prediction results, either for all Dataset Training entries
    // (no santri selected) or for one santri's real Pemeriksaan history (santri selected).
    public array $predictions = [];
    public float $accuracy = 0.0;
    public int $totalCorrect = 0;
    public int $totalIncorrect = 0;

    public function mount(): void
    {
        $this->refreshClassification();
    }

    public function updatedSantriId(): void
    {
        $this->refreshClassification();
    }

    #[Computed]
    public function santriOptions()
    {
        return Santri::orderBy('nama')->get();
    }

    public function refreshClassification(): void
    {
        if ($this->santri_id) {
            $this->calculateSantriPredictions((int) $this->santri_id);
        } else {
            $this->calculateAllPredictions();
        }
    }

    /**
     * Test the model against every Dataset Training entry (evaluates model accuracy).
     */
    public function calculateAllPredictions(): void
    {
        $datasets = DatasetTraining::with(['penyakit', 'gejalas'])->get();
        $totalDatasets = $datasets->count();

        if ($totalDatasets === 0) {
            $this->resetPredictions();
            return;
        }

        $predictionsList = [];
        $correctCount = 0;

        foreach ($datasets as $dataset) {
            $top = $this->classifyGejala($dataset->gejalas->pluck('id')->toArray());
            $isCorrect = $top['penyakit']->id === $dataset->penyakit_id;

            if ($isCorrect) {
                $correctCount++;
            }

            $predictionsList[] = [
                'id' => $dataset->id,
                'penyakit_asli' => $dataset->penyakit,
                'gejalas' => $dataset->gejalas,
                'predicted_penyakit' => $top['penyakit'],
                'probability' => $top['probability'],
                'confidence' => $top['probability'] * 100,
                'is_correct' => $isCorrect,
                'tanggal_periksa' => null,
            ];
        }

        $this->predictions = $predictionsList;
        $this->totalCorrect = $correctCount;
        $this->totalIncorrect = $totalDatasets - $correctCount;
        $this->accuracy = ($correctCount / $totalDatasets) * 100;

        Flux::toast(variant: 'success', text: __('Hasil Klasifikasi seluruh dataset berhasil diperbarui.'));
    }

    /**
     * Classify one santri's real Pemeriksaan (RiwayatKesehatan) history: for each recorded
     * examination, recompute the model's current top prediction from the same gejala and
     * compare it against the diagnosis that was recorded at the time.
     */
    public function calculateSantriPredictions(int $santriId): void
    {
        $riwayats = RiwayatKesehatan::with(['penyakit', 'gejalas'])
            ->where('santri_id', $santriId)
            ->orderByDesc('tanggal_periksa')
            ->get();

        if ($riwayats->isEmpty() || DatasetTraining::count() === 0) {
            $this->resetPredictions();
            return;
        }

        $predictionsList = [];
        $correctCount = 0;

        foreach ($riwayats as $riwayat) {
            $top = $this->classifyGejala($riwayat->gejalas->pluck('id')->toArray());
            $isCorrect = $top['penyakit']->id === $riwayat->penyakit_id;

            if ($isCorrect) {
                $correctCount++;
            }

            $predictionsList[] = [
                'id' => $riwayat->id,
                'penyakit_asli' => $riwayat->penyakit,
                'gejalas' => $riwayat->gejalas,
                'predicted_penyakit' => $top['penyakit'],
                'probability' => $top['probability'],
                'confidence' => $top['probability'] * 100,
                'is_correct' => $isCorrect,
                'tanggal_periksa' => $riwayat->tanggal_periksa,
            ];
        }

        $total = count($riwayats);
        $this->predictions = $predictionsList;
        $this->totalCorrect = $correctCount;
        $this->totalIncorrect = $total - $correctCount;
        $this->accuracy = ($correctCount / $total) * 100;

        Flux::toast(variant: 'success', text: __('Hasil klasifikasi untuk santri terpilih berhasil diperbarui.'));
    }

    private function resetPredictions(): void
    {
        $this->predictions = [];
        $this->accuracy = 0.0;
        $this->totalCorrect = 0;
        $this->totalIncorrect = 0;
    }

    /**
     * Naive Bayes classification (with Laplace smoothing) of the given gejala against
     * Dataset Training, normalized across all penyakit. Returns the top-scoring result:
     * ['penyakit' => Penyakit, 'score' => float, 'probability' => float].
     */
    private function classifyGejala(array $checkedGejalaIds): array
    {
        $totalDatasets = DatasetTraining::count();
        $penyakits = Penyakit::all();
        $gejalas = Gejala::all();

        $results = [];

        foreach ($penyakits as $penyakit) {
            // Prior probability P(C_j)
            $countPenyakit = DatasetTraining::where('penyakit_id', $penyakit->id)->count();
            $prior = ($countPenyakit + 1) / ($totalDatasets + $penyakits->count());

            $likelihood = 1.0;

            foreach ($gejalas as $gejala) {
                // Count entries for this disease with this symptom
                $countSymptomWithDisease = DatasetTraining::where('penyakit_id', $penyakit->id)
                    ->whereHas('gejalas', fn($q) => $q->where('gejala_id', $gejala->id))
                    ->count();

                // P(X_i = 1 | C_j)
                $pSymptomPresent = ($countSymptomWithDisease + 1) / ($countPenyakit + 2);

                $likelihood *= in_array($gejala->id, $checkedGejalaIds) ? $pSymptomPresent : (1.0 - $pSymptomPresent);
            }

            $results[$penyakit->id] = ['penyakit' => $penyakit, 'score' => $prior * $likelihood];
        }

        // Normalize scores to get probabilities
        $totalScore = array_sum(array_column($results, 'score'));

        foreach ($results as $id => $data) {
            $results[$id]['probability'] = $totalScore > 0 ? $data['score'] / $totalScore : 1 / $penyakits->count();
        }

        // Sort by probability desc, return the top match
        uasort($results, fn($a, $b) => $b['probability'] <=> $a['probability']);

        return reset($results);
    }
};
?>

<div class="flex h-full w-full flex-1 flex-col gap-6 p-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">{{ __('Pengujian & Klasifikasi Naive Bayes') }}</flux:heading>
            <flux:text>
                {{ $santri_id
                    ? __('Menampilkan hasil klasifikasi untuk riwayat pemeriksaan santri yang dipilih.')
                    : __('Halaman evaluasi model Naive Bayes secara otomatis memKlasifikasi seluruh data yang tersimpan di Dataset Training.') }}
            </flux:text>
        </div>
        <div class="flex items-center gap-2">
            @php
                $santriOptionsForJs = $this->santriOptions->map(fn ($o) => [
                    'id' => $o->id,
                    'label' => $o->nis . ' - ' . $o->nama,
                    'search' => strtolower($o->nis . ' ' . $o->nama),
                ])->values();
                $selectedSantri = $santri_id ? $this->santriOptions->firstWhere('id', $santri_id) : null;
            @endphp

            <!-- Searchable "select": still a dropdown of options (not a free-text field) — the
                 search box just filters the option list below it. -->
            <div
                class="relative w-full sm:w-64"
                x-data="{
                    open: false,
                    query: '',
                    options: @js($santriOptionsForJs),
                    label: @js($selectedSantri ? $selectedSantri->nis . ' - ' . $selectedSantri->nama : null),
                    get filtered() {
                        const q = this.query.trim().toLowerCase();
                        return q === '' ? this.options : this.options.filter(o => o.search.includes(q));
                    },
                    select(option) {
                        this.$wire.santri_id = option ? option.id : null;
                        this.label = option ? option.label : null;
                        this.open = false;
                    },
                }"
                @click.outside="open = false"
                x-effect="if (! open) query = ''"
            >
                <button
                    type="button"
                    @click="open = ! open; $nextTick(() => open && $refs.santriSearch.focus())"
                    class="flex h-10 w-full items-center justify-between gap-2 rounded-lg border border-zinc-200 border-b-zinc-300/80 bg-white px-3 text-base text-zinc-700 shadow-xs sm:text-sm dark:border-white/10 dark:bg-white/10 dark:text-zinc-300"
                >
                    <span x-text="label || '{{ __('Semua (Uji Dataset Training)') }}'" class="truncate"></span>
                    <flux:icon name="chevron-down" class="size-4 shrink-0 text-zinc-400" />
                </button>

                <div
                    x-show="open"
                    x-cloak
                    x-transition
                    class="absolute z-50 mt-1 w-full overflow-hidden rounded-lg border border-zinc-200 bg-white shadow-lg dark:border-zinc-600 dark:bg-zinc-700"
                >
                    <div class="border-b border-zinc-200 p-2 dark:border-zinc-600">
                        <flux:input x-ref="santriSearch" x-model="query" size="sm" placeholder="{{ __('Cari nama atau NIS...') }}" icon="magnifying-glass" @keydown.escape="open = false" />
                    </div>
                    <div class="max-h-60 overflow-y-auto p-1">
                        <button
                            type="button"
                            @click="select(null)"
                            class="flex w-full items-center rounded-md px-2 py-1.5 text-start text-sm font-medium text-zinc-800 hover:bg-zinc-50 dark:text-white dark:hover:bg-zinc-600"
                        >
                            {{ __('Semua (Uji Dataset Training)') }}
                        </button>
                        <template x-for="option in filtered" :key="option.id">
                            <button
                                type="button"
                                @click="select(option)"
                                class="flex w-full items-center rounded-md px-2 py-1.5 text-start text-sm font-medium text-zinc-800 hover:bg-zinc-50 dark:text-white dark:hover:bg-zinc-600"
                                x-text="option.label"
                            ></button>
                        </template>
                        <div x-show="filtered.length === 0" class="px-2 py-1.5 text-sm text-zinc-400" x-text="'{{ __('Tidak ditemukan.') }}'"></div>
                    </div>
                </div>
            </div>

            <flux:button icon="arrow-path" variant="primary" wire:click="refreshClassification">
                {{ $santri_id ? __('Perbarui Klasifikasi Santri') : __('Perbarui Klasifikasi Dataset') }}
            </flux:button>
        </div>
    </div>

    <!-- Accuracy & Evaluation Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <flux:card class="flex items-center gap-4 border-l-4 border-blue-500">
            <div class="p-3 bg-blue-50 text-blue-600 dark:bg-blue-950/20 dark:text-blue-400 rounded-lg">
                <flux:icon name="circle-stack" class="size-6" />
            </div>
            <div>
                <flux:text size="sm" class="text-zinc-500">{{ $santri_id ? __('Total Pemeriksaan') : __('Total Dataset') }}</flux:text>
                <flux:heading size="lg">{{ count($predictions) }}</flux:heading>
            </div>
        </flux:card>

        <flux:card class="flex items-center gap-4 border-l-4 border-emerald-500">
            <div class="p-3 bg-emerald-50 text-emerald-600 dark:bg-emerald-950/20 dark:text-emerald-400 rounded-lg">
                <flux:icon name="check-circle" class="size-6" />
            </div>
            <div>
                <flux:text size="sm" class="text-zinc-500">{{ __('Klasifikasi Cocok') }}</flux:text>
                <flux:heading size="lg" class="text-emerald-600 dark:text-emerald-400">{{ $totalCorrect }}</flux:heading>
            </div>
        </flux:card>

        <flux:card class="flex items-center gap-4 border-l-4 border-red-500">
            <div class="p-3 bg-red-50 text-red-600 dark:bg-red-950/20 dark:text-red-400 rounded-lg">
                <flux:icon name="x-circle" class="size-6" />
            </div>
            <div>
                <flux:text size="sm" class="text-zinc-500">{{ __('Klasifikasi Tidak Cocok') }}</flux:text>
                <flux:heading size="lg" class="text-red-600 dark:text-red-400">{{ $totalIncorrect }}</flux:heading>
            </div>
        </flux:card>

        <flux:card class="flex items-center gap-4 border-l-4 border-purple-500">
            <div class="p-3 bg-purple-50 text-purple-600 dark:bg-purple-950/20 dark:text-purple-400 rounded-lg">
                <flux:icon name="academic-cap" class="size-6" />
            </div>
            <div>
                <flux:text size="sm" class="text-zinc-500">{{ __('Akurasi Model') }}</flux:text>
                <flux:heading size="lg" class="text-purple-600 dark:text-purple-400">{{ number_format($accuracy, 2) }}%</flux:heading>
            </div>
        </flux:card>
    </div>

    <!-- Predictions Evaluation Table -->
    <flux:card class="overflow-x-auto p-0">
        @if(count($predictions) > 0)
        <flux:table>
            <flux:table.columns>
                <flux:table.column class="pl-4">{{ __('No') }}</flux:table.column>
                @if($santri_id)
                    <flux:table.column>{{ __('Tanggal') }}</flux:table.column>
                @endif
                <flux:table.column>{{ $santri_id ? __('Diagnosa Tercatat') : __('Penyakit Target (Dataset)') }}</flux:table.column>
                <flux:table.column>{{ __('Gejala Penyakit') }}</flux:table.column>
                <flux:table.column>{{ __('Hasil Klasifikasi Naive Bayes') }}</flux:table.column>
                <flux:table.column>{{ __('Probabilitas') }}</flux:table.column>
                <flux:table.column>{{ __('Keyakinan') }}</flux:table.column>
                <flux:table.column class="w-24 text-center">{{ __('Status') }}</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @foreach ($predictions as $index => $pred)
                <flux:table.row :key="$pred['id']">
                    <flux:table.cell class="pl-4 font-semibold text-zinc-500">{{ $index + 1 }}</flux:table.cell>
                    @if($santri_id)
                        <flux:table.cell class="whitespace-nowrap">
                            {{ $pred['tanggal_periksa'] ? \Carbon\Carbon::parse($pred['tanggal_periksa'])->translatedFormat('d M Y') : '—' }}
                        </flux:table.cell>
                    @endif
                    <flux:table.cell variant="strong" class="whitespace-nowrap">
                        {{ $pred['penyakit_asli']->nama_penyakit }}
                    </flux:table.cell>
                    <flux:table.cell class="max-w-md">
                        <div class="flex flex-wrap gap-1">
                            @foreach ($pred['gejalas'] as $gejala)
                            <flux:badge size="sm" variant="outline" color="blue">
                                {{ $gejala->nama_gejala }}
                            </flux:badge>
                            @endforeach
                        </div>
                    </flux:table.cell>
                    <flux:table.cell class="font-semibold {{ $pred['is_correct'] ? 'text-zinc-900 dark:text-white' : 'text-red-600 dark:text-red-400' }}">
                        {{ $pred['predicted_penyakit']->nama_penyakit }}
                    </flux:table.cell>
                    <flux:table.cell class="font-mono text-xs">{{ number_format($pred['probability'], 5) }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:badge color="blue" variant="outline">{{ number_format($pred['confidence'], 2) }}%</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell class="text-center">
                        @if($pred['is_correct'])
                        <flux:badge color="emerald" size="sm">{{ __('Cocok') }}</flux:badge>
                        @else
                        <flux:badge color="red" size="sm">{{ __('Tidak Cocok') }}</flux:badge>
                        @endif
                    </flux:table.cell>
                </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
        @else
        <div class="text-center p-12 text-zinc-400 dark:text-zinc-500">
            <flux:icon name="circle-stack" class="size-12 mx-auto mb-3" />
            @if($santri_id)
                <div class="font-medium text-lg">{{ __('Belum Ada Data Pemeriksaan') }}</div>
                <flux:text class="text-sm mt-1">{{ __('Santri ini belum memiliki riwayat pemeriksaan, atau Dataset Training masih kosong.') }}</flux:text>
            @else
                <div class="font-medium text-lg">{{ __('Belum Ada Dataset Training') }}</div>
                <flux:text class="text-sm mt-1">{{ __('Silakan tambahkan dataset training terlebih dahulu sebelum menjalankan klasifikasi.') }}</flux:text>
            @endif
        </div>
        @endif
    </flux:card>
</div>
