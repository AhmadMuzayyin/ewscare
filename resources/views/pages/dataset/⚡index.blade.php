<?php

use App\Models\DatasetTraining;
use App\Models\Penyakit;
use App\Models\Gejala;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Title;
use Livewire\Attributes\Computed;
use Flux\Flux;

new #[Title('Dataset Training')] class extends Component {
    use WithPagination;

    public string $search = '';

    // Form states
    public ?DatasetTraining $editingDataset = null;
    public ?int $penyakit_id = null;
    public array $selectedGejalas = [];

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function datasets()
    {
        return DatasetTraining::query()
            ->with(['penyakit', 'gejalas'])
            ->when($this->search, function ($query) {
                $query->whereHas('penyakit', function ($q) {
                    $q->where('nama_penyakit', 'like', '%' . $this->search . '%');
                });
            })
            ->latest()
            ->paginate(10);
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

    public function openCreateModal(): void
    {
        $this->editingDataset = null;
        $this->penyakit_id = null;
        $this->selectedGejalas = [];

        $this->modal('dataset-modal')->show();
    }

    public function editDataset(int $id): void
    {
        $this->editingDataset = DatasetTraining::findOrFail($id);
        $this->penyakit_id = $this->editingDataset->penyakit_id;
        $this->selectedGejalas = $this->editingDataset->gejalas->pluck('id')->map(fn($id) => (string)$id)->toArray();

        $this->modal('dataset-modal')->show();
    }

    public function saveDataset(): void
    {
        $rules = [
            'penyakit_id' => 'required|exists:penyakits,id',
            'selectedGejalas' => 'required|array|min:1',
            'selectedGejalas.*' => 'exists:gejalas,id',
        ];

        $validated = $this->validate($rules);

        if ($this->editingDataset) {
            $this->editingDataset->update(['penyakit_id' => $validated['penyakit_id']]);
            $this->editingDataset->gejalas()->sync($validated['selectedGejalas']);
            Flux::toast(variant: 'success', text: __('Dataset training berhasil diperbarui.'));
        } else {
            $dataset = DatasetTraining::create(['penyakit_id' => $validated['penyakit_id']]);
            $dataset->gejalas()->sync($validated['selectedGejalas']);
            Flux::toast(variant: 'success', text: __('Dataset training baru berhasil ditambahkan.'));
        }

        $this->modal('dataset-modal')->close();
    }

    public function deleteDataset(int $id): void
    {
        DatasetTraining::findOrFail($id)->delete();
        Flux::toast(variant: 'success', text: __('Dataset training berhasil dihapus.'));
    }
};
?>

<div class="flex h-full w-full flex-1 flex-col gap-6 p-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl" level="1">{{ __('Dataset Training') }}</flux:heading>
            <flux:text>{{ __('Kelola data latih kombinasi gejala dan penyakit untuk model Naive Bayes.') }}</flux:text>
        </div>
        <flux:button icon="plus" variant="primary" wire:click="openCreateModal">{{ __('Tambah Dataset') }}</flux:button>
    </div>

    <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
        <flux:input class="flex-1" wire:model.live="search" placeholder="Cari berdasarkan nama penyakit..." icon="magnifying-glass" />
    </div>

    <flux:card class="overflow-x-auto p-0">
        <flux:table :paginate="$this->datasets">
            <flux:table.columns>
                <flux:table.column class="pl-4">{{ __('Penyakit') }}</flux:table.column>
                <flux:table.column>{{ __('Gejala-Gejala') }}</flux:table.column>
                <flux:table.column class="w-24">{{ __('Aksi') }}</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @foreach ($this->datasets as $dataset)
                    <flux:table.row :key="$dataset->id">
                        <flux:table.cell class="pl-4 whitespace-nowrap" variant="strong">
                            {{ $dataset->penyakit->nama_penyakit }}
                            <flux:badge size="sm" :color="$dataset->penyakit->is_menular ? 'red' : 'zinc'" class="ml-1">
                                {{ $dataset->penyakit->is_menular ? __('Menular') : __('Tidak Menular') }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>
                            <div class="flex flex-wrap gap-1">
                                @foreach ($dataset->gejalas as $gejala)
                                    <flux:badge size="sm" variant="outline" color="blue">
                                        [{{ $gejala->kode_gejala }}] {{ $gejala->nama_gejala }}
                                    </flux:badge>
                                @endforeach
                            </div>
                        </flux:table.cell>
                        <flux:table.cell class="flex gap-2">
                            <flux:button variant="ghost" icon="pencil-square" size="sm" wire:click="editDataset({{ $dataset->id }})" />
                            <flux:button variant="ghost" icon="trash" size="sm" class="text-red-500 hover:text-red-600" 
                                wire:confirm="Apakah Anda yakin ingin menghapus data latih ini?" 
                                wire:click="deleteDataset({{ $dataset->id }})" />
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    </flux:card>

    <!-- Modal Form Dataset -->
    <flux:modal name="dataset-modal" class="md:w-[500px]">
        <form wire:submit.prevent="saveDataset" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ $editingDataset ? __('Edit Dataset Training') : __('Tambah Dataset Training') }}</flux:heading>
                <flux:text class="mt-2">{{ __('Hubungkan penyakit dengan kombinasi gejala di bawah ini.') }}</flux:text>
            </div>

            <flux:select label="{{ __('Penyakit') }}" wire:model="penyakit_id" required>
                <option value="">{{ __('Pilih Penyakit') }}</option>
                @foreach ($this->penyakitOptions as $option)
                    <option value="{{ $option->id }}">{{ $option->nama_penyakit }}</option>
                @endforeach
            </flux:select>

            <div class="space-y-2">
                <flux:label>{{ __('Pilih Gejala yang Sesuai') }}</flux:label>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 max-h-60 overflow-y-auto p-2 border border-zinc-200 dark:border-zinc-700 rounded-lg">
                    @foreach ($this->gejalaOptions as $gejala)
                        <label class="flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-300 cursor-pointer">
                            <input type="checkbox" wire:model="selectedGejalas" value="{{ $gejala->id }}" class="rounded border-zinc-300 text-blue-600 focus:ring-blue-500">
                            <span>[{{ $gejala->kode_gejala }}] {{ $gejala->nama_gejala }}</span>
                        </label>
                    @endforeach
                </div>
                @error('selectedGejalas') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
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
