<?php

use App\Models\DatasetTraining;
use App\Models\Gejala;
use App\Models\Penyakit;
use App\Models\Santri;
use Livewire\Component;
use Livewire\Attributes\Title;
use Livewire\Attributes\Computed;
use Flux\Flux;

new #[Title('Klasifikasi & Pengujian Model')] class extends Component {
    public ?int $santri_id = null;

    // Array to store the simulated prediction results for all datasets
    public array $predictions = [];
    public float $accuracy = 0.0;
    public int $totalCorrect = 0;
    public int $totalIncorrect = 0;

    public function mount(): void
    {
        $this->calculateAllPredictions();
    }

    #[Computed]
    public function santriOptions()
    {
        return Santri::orderBy('nama')->get();
    }

    public function calculateAllPredictions(): void
    {
        $datasets = DatasetTraining::with(['penyakit', 'gejalas'])->get();
        $totalDatasets = $datasets->count();

        if ($totalDatasets === 0) {
            $this->predictions = [];
            $this->accuracy = 0.0;
            $this->totalCorrect = 0;
            $this->totalIncorrect = 0;
            return;
        }

        $penyakits = Penyakit::all();
        $gejalas = Gejala::all();

        $predictionsList = [];
        $correctCount = 0;

        foreach ($datasets as $dataset) {
            $checkedGejalaIds = $dataset->gejalas->pluck('id')->toArray();

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

                    if (in_array($gejala->id, $checkedGejalaIds)) {
                        $likelihood *= $pSymptomPresent;
                    } else {
                        $likelihood *= (1.0 - $pSymptomPresent);
                    }
                }

                $results[$penyakit->id] = [
                    'penyakit' => $penyakit,
                    'score' => $prior * $likelihood
                ];
            }

            // Normalize scores to get probabilities
            $totalScore = array_sum(array_column($results, 'score'));

            if ($totalScore > 0) {
                foreach ($results as $id => $data) {
                    $results[$id]['probability'] = $data['score'] / $totalScore;
                }
            } else {
                foreach ($results as $id => $data) {
                    $results[$id]['probability'] = 1 / $penyakits->count();
                }
            }

            // Sort by probability desc
            uasort($results, fn($a, $b) => $b['probability'] <=> $a['probability']);

            $highestResult = reset($results);
            $predictedPenyakit = $highestResult['penyakit'];
            $prob = $highestResult['probability'];
            $confidence = $prob * 100;
            $isCorrect = $predictedPenyakit->id === $dataset->penyakit_id;

            if ($isCorrect) {
                $correctCount++;
            }

            $predictionsList[] = [
                'id' => $dataset->id,
                'penyakit_asli' => $dataset->penyakit,
                'gejalas' => $dataset->gejalas,
                'predicted_penyakit' => $predictedPenyakit,
                'probability' => $prob,
                'confidence' => $confidence,
                'is_correct' => $isCorrect
            ];
        }

        $this->predictions = $predictionsList;
        $this->totalCorrect = $correctCount;
        $this->totalIncorrect = $totalDatasets - $correctCount;
        $this->accuracy = ($correctCount / $totalDatasets) * 100;

        Flux::toast(variant: 'success', text: __('Hasil Klasifikasi seluruh dataset berhasil diperbarui.'));
    }
};
?>

<div class="flex h-full w-full flex-1 flex-col gap-6 p-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">{{ __('Pengujian & Klasifikasi Naive Bayes') }}</flux:heading>
            <flux:text>{{ __('Halaman evaluasi model Naive Bayes secara otomatis memKlasifikasi seluruh data yang tersimpan di Dataset Training.') }}</flux:text>
        </div>
        <div class="flex items-center gap-2">
            <flux:select wire:model="santri_id" placeholder="Pilih Santri" class="w-full sm:w-64">
                <option value="">{{ __('Pilih Santri') }}</option>
                @foreach ($this->santriOptions as $option)
                    <option value="{{ $option->id }}">{{ $option->nis }} - {{ $option->nama }}</option>
                @endforeach
            </flux:select>
            <flux:button icon="arrow-path" variant="primary" wire:click="calculateAllPredictions">
                {{ __('Perbarui Klasifikasi Dataset') }}
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
                <flux:text size="sm" class="text-zinc-500">{{ __('Total Dataset') }}</flux:text>
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
                <flux:table.column>{{ __('Penyakit Target (Dataset)') }}</flux:table.column>
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
                        </flux:cell>
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
            <div class="font-medium text-lg">{{ __('Belum Ada Dataset Training') }}</div>
            <flux:text class="text-sm mt-1">{{ __('Silakan tambahkan dataset training terlebih dahulu sebelum menjalankan klasifikasi.') }}</flux:text>
        </div>
        @endif
    </flux:card>
</div>
