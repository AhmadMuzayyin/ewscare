<?php

use App\Models\DatasetTraining;
use App\Models\EwsAlert;
use App\Models\Gejala;
use App\Models\Kamar;
use App\Models\Penyakit;
use App\Models\RiwayatKesehatan;
use App\Models\Santri;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Title;
use Livewire\Attributes\Computed;
use Flux\Flux;

new #[Title('Data Pemeriksaan')] class extends Component {
    use WithPagination;

    public string $search = '';

    // Form states
    public ?RiwayatKesehatan $editingPemeriksaan = null;
    public ?int $santri_id = null;
    public ?int $penyakit_id = null;
    public array $selectedGejalas = [];
    public string $tanggal_periksa = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function pemeriksaans()
    {
        return RiwayatKesehatan::query()
            ->with(['santri', 'kamar.wilayah', 'penyakit', 'gejalas'])
            ->when($this->search, function ($query) {
                $query->whereHas('santri', function ($q) {
                    $q->where('nama', 'like', '%' . $this->search . '%')
                        ->orWhere('nis', 'like', '%' . $this->search . '%');
                })->orWhereHas('penyakit', function ($q) {
                    $q->where('nama_penyakit', 'like', '%' . $this->search . '%');
                });
            })
            ->latest('tanggal_periksa')
            ->paginate(10);
    }

    #[Computed]
    public function santriOptions()
    {
        return Santri::orderBy('nama')->get();
    }

    #[Computed]
    public function penyakitOptions()
    {
        return Penyakit::orderBy('nama_penyakit')->get();
    }

    #[Computed]
    public function gejalaOptions()
    {
        return Gejala::orderBy('kode_gejala')->get();
    }

    /**
     * Live preview of the Naive Bayes probability/confidence for the currently selected
     * penyakit, based on the currently checked gejala. Null when there's not enough
     * input yet to compute anything.
     */
    #[Computed]
    public function previewResult(): ?array
    {
        if (!$this->penyakit_id || empty($this->selectedGejalas)) {
            return null;
        }

        return $this->calculateProbability((int) $this->penyakit_id, array_map('intval', $this->selectedGejalas));
    }

    public function openCreateModal(): void
    {
        $this->editingPemeriksaan = null;
        $this->santri_id = null;
        $this->penyakit_id = null;
        $this->selectedGejalas = [];
        $this->tanggal_periksa = now()->toDateString();

        $this->modal('pemeriksaan-modal')->show();
    }

    public function editPemeriksaan(int $id): void
    {
        $this->editingPemeriksaan = RiwayatKesehatan::with('gejalas')->findOrFail($id);
        $this->santri_id = $this->editingPemeriksaan->santri_id;
        $this->penyakit_id = $this->editingPemeriksaan->penyakit_id;
        $this->selectedGejalas = $this->editingPemeriksaan->gejalas->pluck('id')->map(fn($id) => (string) $id)->toArray();
        $this->tanggal_periksa = $this->editingPemeriksaan->tanggal_periksa;

        $this->modal('pemeriksaan-modal')->show();
    }

    public function savePemeriksaan(): void
    {
        $rules = [
            'santri_id' => 'required|exists:santris,id',
            'penyakit_id' => 'required|exists:penyakits,id',
            'selectedGejalas' => 'required|array|min:1',
            'selectedGejalas.*' => 'exists:gejalas,id',
            'tanggal_periksa' => 'required|date',
        ];

        $validated = $this->validate($rules);

        $santri = Santri::findOrFail($validated['santri_id']);
        $gejalaIds = array_map('intval', $validated['selectedGejalas']);
        $result = $this->calculateProbability((int) $validated['penyakit_id'], $gejalaIds);

        $payload = [
            'santri_id' => $validated['santri_id'],
            'penyakit_id' => $validated['penyakit_id'],
            'kamar_id' => $santri->kamar_id,
            'tanggal_periksa' => $validated['tanggal_periksa'],
            'probabilitas' => $result['probabilitas'],
            'tingkat_keyakinan' => $result['tingkat_keyakinan'],
        ];

        if ($this->editingPemeriksaan) {
            $oldKamarId = $this->editingPemeriksaan->kamar_id;
            $oldPenyakitId = $this->editingPemeriksaan->penyakit_id;

            $this->editingPemeriksaan->update($payload);
            $this->editingPemeriksaan->gejalas()->sync($validated['selectedGejalas']);

            $isNewCase = $oldKamarId !== $payload['kamar_id'] || $oldPenyakitId !== $payload['penyakit_id'];

            if ($isNewCase) {
                $this->releaseEwsAlert($oldKamarId, $oldPenyakitId);
            }

            $this->applyEwsAlert($this->editingPemeriksaan->fresh(), $isNewCase);

            Flux::toast(variant: 'success', text: __('Data pemeriksaan berhasil diperbarui.'));
        } else {
            $riwayat = RiwayatKesehatan::create($payload);
            $riwayat->gejalas()->sync($validated['selectedGejalas']);

            $this->applyEwsAlert($riwayat, true);

            Flux::toast(variant: 'success', text: __('Data pemeriksaan baru berhasil ditambahkan.'));
        }

        $this->modal('pemeriksaan-modal')->close();
    }

    public function deletePemeriksaan(int $id): void
    {
        $riwayat = RiwayatKesehatan::with('penyakit')->findOrFail($id);

        if ($riwayat->penyakit?->is_menular) {
            $this->releaseEwsAlert($riwayat->kamar_id, $riwayat->penyakit_id);
        }

        $riwayat->delete();
        Flux::toast(variant: 'success', text: __('Data pemeriksaan berhasil dihapus.'));
    }

    /**
     * Naive Bayes probability of the given penyakit given the checked gejala, computed
     * against Dataset Training (same algorithm as the Klasifikasi page), normalized across
     * all penyakit so the result is directly comparable/consistent with that page.
     *
     * @return array{probabilitas: float, tingkat_keyakinan: float}
     */
    private function calculateProbability(int $penyakitId, array $gejalaIds): array
    {
        $totalDatasets = DatasetTraining::count();
        $penyakits = Penyakit::all();

        if ($totalDatasets === 0 || $penyakits->isEmpty()) {
            return ['probabilitas' => 0.0, 'tingkat_keyakinan' => 0.0];
        }

        $gejalas = Gejala::all();
        $scores = [];

        foreach ($penyakits as $penyakit) {
            $countPenyakit = DatasetTraining::where('penyakit_id', $penyakit->id)->count();
            $prior = ($countPenyakit + 1) / ($totalDatasets + $penyakits->count());

            $likelihood = 1.0;

            foreach ($gejalas as $gejala) {
                $countSymptomWithDisease = DatasetTraining::where('penyakit_id', $penyakit->id)
                    ->whereHas('gejalas', fn($q) => $q->where('gejala_id', $gejala->id))
                    ->count();

                $pSymptomPresent = ($countSymptomWithDisease + 1) / ($countPenyakit + 2);

                $likelihood *= in_array($gejala->id, $gejalaIds) ? $pSymptomPresent : (1.0 - $pSymptomPresent);
            }

            $scores[$penyakit->id] = $prior * $likelihood;
        }

        $totalScore = array_sum($scores);

        $probability = $totalScore > 0
            ? ($scores[$penyakitId] ?? 0) / $totalScore
            : 1 / $penyakits->count();

        return [
            'probabilitas' => round($probability, 4),
            'tingkat_keyakinan' => round($probability * 100, 2),
        ];
    }

    /**
     * Create or bump an active EWS alert for the given case, when the disease is contagious.
     */
    private function applyEwsAlert(RiwayatKesehatan $riwayat, bool $isNewCase): void
    {
        if (!$isNewCase) {
            return;
        }

        $penyakit = Penyakit::find($riwayat->penyakit_id);
        if (!$penyakit || !$penyakit->is_menular) {
            return;
        }

        $alert = EwsAlert::where('kamar_id', $riwayat->kamar_id)
            ->where('penyakit_id', $riwayat->penyakit_id)
            ->where('status', 'Aktif')
            ->first();

        if ($alert) {
            $alert->increment('jumlah_kasus');
            return;
        }

        $kamar = Kamar::with('wilayah')->find($riwayat->kamar_id);

        EwsAlert::create([
            'kamar_id' => $riwayat->kamar_id,
            'wilayah' => $kamar?->wilayah?->nama_wilayah,
            'blok' => $kamar?->blok,
            'penyakit_id' => $riwayat->penyakit_id,
            'jumlah_kasus' => 1,
            'status' => 'Aktif',
            'tanggal_dideteksi' => now()->toDateString(),
        ]);
    }

    /**
     * Release one case from the matching active EWS alert, removing it once empty.
     */
    private function releaseEwsAlert(int $kamarId, int $penyakitId): void
    {
        $alert = EwsAlert::where('kamar_id', $kamarId)
            ->where('penyakit_id', $penyakitId)
            ->where('status', 'Aktif')
            ->first();

        if (!$alert) {
            return;
        }

        if ($alert->jumlah_kasus <= 1) {
            $alert->delete();
        } else {
            $alert->decrement('jumlah_kasus');
        }
    }
};
?>

<div class="flex h-full w-full flex-1 flex-col gap-6 p-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl" level="1">{{ __('Data Pemeriksaan') }}</flux:heading>
            <flux:text>{{ __('Catat hasil pemeriksaan kesehatan santri beserta gejala dan penyakit yang terdeteksi.') }}</flux:text>
        </div>
        <flux:button icon="plus" variant="primary" wire:click="openCreateModal">{{ __('Tambah Pemeriksaan') }}</flux:button>
    </div>

    <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
        <flux:input class="flex-1" wire:model.live="search" placeholder="Cari berdasarkan nama santri, NIS, atau penyakit..." icon="magnifying-glass" />
    </div>

    <flux:card class="overflow-x-auto p-0">
        <flux:table :paginate="$this->pemeriksaans">
            <flux:table.columns>
                <flux:table.column class="pl-4">{{ __('Tanggal') }}</flux:table.column>
                <flux:table.column>{{ __('Santri') }}</flux:table.column>
                <flux:table.column>{{ __('Kamar') }}</flux:table.column>
                <flux:table.column>{{ __('Penyakit') }}</flux:table.column>
                <flux:table.column>{{ __('Gejala') }}</flux:table.column>
                <flux:table.column>{{ __('Keyakinan') }}</flux:table.column>
                <flux:table.column class="w-24">{{ __('Aksi') }}</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @foreach ($this->pemeriksaans as $riwayat)
                <flux:table.row :key="$riwayat->id">
                    <flux:table.cell class="pl-4 whitespace-nowrap">{{ \Carbon\Carbon::parse($riwayat->tanggal_periksa)->translatedFormat('d M Y') }}</flux:table.cell>
                    <flux:table.cell>
                        <div class="font-medium text-zinc-900 dark:text-white">{{ $riwayat->santri->nama }}</div>
                        <div class="text-xs text-zinc-500">{{ $riwayat->santri->nis }}</div>
                    </flux:table.cell>
                    <flux:table.cell>
                        @if($riwayat->kamar)
                            <flux:text size="sm">{{ $riwayat->kamar->wilayah->nama_wilayah }} - {{ $riwayat->kamar->blok }} - {{ $riwayat->kamar->nama_kamar }}</flux:text>
                        @else
                            <flux:text size="sm" class="text-zinc-400">—</flux:text>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell class="whitespace-nowrap">
                        <span>{{ $riwayat->penyakit->nama_penyakit }}</span>
                        <flux:badge size="sm" :color="$riwayat->penyakit->is_menular ? 'red' : 'zinc'" class="ml-1">
                            {{ $riwayat->penyakit->is_menular ? __('Menular') : __('Tidak Menular') }}
                        </flux:badge>
                    </flux:table.cell>
                    <flux:table.cell class="max-w-xs">
                        <div class="flex flex-wrap gap-1">
                            @foreach ($riwayat->gejalas as $gejala)
                            <flux:badge size="sm" variant="outline" color="blue">{{ $gejala->nama_gejala }}</flux:badge>
                            @endforeach
                        </div>
                    </flux:table.cell>
                    <flux:table.cell>
                        <flux:badge color="blue" variant="outline">{{ number_format($riwayat->tingkat_keyakinan, 2) }}%</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell class="flex gap-2">
                        <flux:button variant="ghost" icon="pencil-square" size="sm" wire:click="editPemeriksaan({{ $riwayat->id }})" />
                        <flux:button variant="ghost" icon="trash" size="sm" class="text-red-500 hover:text-red-600"
                            wire:confirm="Apakah Anda yakin ingin menghapus data pemeriksaan ini?"
                            wire:click="deletePemeriksaan({{ $riwayat->id }})" />
                    </flux:table.cell>
                </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    </flux:card>

    <!-- Modal Form Pemeriksaan -->
    <flux:modal name="pemeriksaan-modal" class="md:w-[500px]">
        <form wire:submit.prevent="savePemeriksaan" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ $editingPemeriksaan ? __('Edit Pemeriksaan') : __('Tambah Pemeriksaan') }}</flux:heading>
                <flux:text class="mt-2">{{ __('Lengkapi hasil pemeriksaan kesehatan santri di bawah ini.') }}</flux:text>
            </div>

            <flux:select label="{{ __('Santri') }}" wire:model="santri_id" required>
                <option value="">{{ __('Pilih Santri') }}</option>
                @foreach ($this->santriOptions as $option)
                    <option value="{{ $option->id }}">{{ $option->nis }} - {{ $option->nama }}</option>
                @endforeach
            </flux:select>

            <flux:select label="{{ __('Penyakit') }}" wire:model.live="penyakit_id" required>
                <option value="">{{ __('Pilih Penyakit') }}</option>
                @foreach ($this->penyakitOptions as $option)
                    <option value="{{ $option->id }}">{{ $option->nama_penyakit }}</option>
                @endforeach
            </flux:select>

            <div class="space-y-2">
                <flux:label>{{ __('Gejala yang Dialami') }}</flux:label>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 max-h-60 overflow-y-auto p-2 border border-zinc-200 dark:border-zinc-700 rounded-lg">
                    @foreach ($this->gejalaOptions as $gejala)
                        <label class="flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-300 cursor-pointer">
                            <input type="checkbox" wire:model.live="selectedGejalas" value="{{ $gejala->id }}" class="rounded border-zinc-300 text-blue-600 focus:ring-blue-500">
                            <span>[{{ $gejala->kode_gejala }}] {{ $gejala->nama_gejala }}</span>
                        </label>
                    @endforeach
                </div>
                @error('selectedGejalas') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
            </div>

            <flux:input type="date" label="{{ __('Tanggal Periksa') }}" wire:model="tanggal_periksa" required />

            <!-- Probabilitas & Tingkat Keyakinan dihitung otomatis oleh sistem (Naive Bayes) berdasarkan
                 penyakit dan gejala yang dipilih di atas — tidak bisa diisi manual. -->
            <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-800/50">
                <flux:text size="sm" class="text-zinc-500">{{ __('Hasil Perhitungan Sistem (Naive Bayes)') }}</flux:text>
                @if ($this->previewResult)
                    <div class="mt-2 grid grid-cols-2 gap-4">
                        <div>
                            <flux:text size="xs" class="text-zinc-500">{{ __('Probabilitas') }}</flux:text>
                            <flux:heading size="lg" class="font-mono">{{ number_format($this->previewResult['probabilitas'], 4) }}</flux:heading>
                        </div>
                        <div>
                            <flux:text size="xs" class="text-zinc-500">{{ __('Tingkat Keyakinan') }}</flux:text>
                            <flux:heading size="lg">{{ number_format($this->previewResult['tingkat_keyakinan'], 2) }}%</flux:heading>
                        </div>
                    </div>
                @elseif (\App\Models\DatasetTraining::count() === 0)
                    <flux:text size="sm" class="mt-1 text-amber-600 dark:text-amber-400">
                        {{ __('Belum ada Dataset Training, probabilitas belum dapat dihitung.') }}
                    </flux:text>
                @else
                    <flux:text size="sm" class="mt-1 text-zinc-400">
                        {{ __('Pilih penyakit dan minimal satu gejala untuk melihat hasil perhitungan.') }}
                    </flux:text>
                @endif
            </div>

            <div class="flex gap-2">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Batal') }}</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">{{ __('Simpan') }}</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
