<?php

use App\Models\Penyakit;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Title;
use Livewire\Attributes\Computed;
use Flux\Flux;

new #[Title('Data Penyakit')] class extends Component {
    use WithPagination;

    public string $search = '';

    // Form states
    public ?Penyakit $editingPenyakit = null;
    public string $kode_penyakit = '';
    public string $nama_penyakit = '';
    public bool $is_menular = false;
    public string $solusi_pencegahan = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function penyakits()
    {
        return Penyakit::query()
            ->when($this->search, function ($query) {
                $query->where('nama_penyakit', 'like', '%' . $this->search . '%')
                    ->orWhere('kode_penyakit', 'like', '%' . $this->search . '%');
            })
            ->orderBy('kode_penyakit')
            ->paginate(10);
    }

    public function openCreateModal(): void
    {
        $this->editingPenyakit = null;

        $lastPenyakit = Penyakit::orderBy('id', 'desc')->first();
        if ($lastPenyakit && preg_match('/P(\d+)/', $lastPenyakit->kode_penyakit, $matches)) {
            $nextNumber = intval($matches[1]) + 1;
            $this->kode_penyakit = 'P' . str_pad((string)$nextNumber, 2, '0', STR_PAD_LEFT);
        } else {
            $this->kode_penyakit = 'P01';
        }

        $this->nama_penyakit = '';
        $this->is_menular = false;
        $this->solusi_pencegahan = '';

        $this->modal('penyakit-modal')->show();
    }

    public function editPenyakit(int $id): void
    {
        $this->editingPenyakit = Penyakit::findOrFail($id);
        $this->kode_penyakit = $this->editingPenyakit->kode_penyakit;
        $this->nama_penyakit = $this->editingPenyakit->nama_penyakit;
        $this->is_menular = (bool)$this->editingPenyakit->is_menular;
        $this->solusi_pencegahan = $this->editingPenyakit->solusi_pencegahan ?? '';

        $this->modal('penyakit-modal')->show();
    }

    public function savePenyakit(): void
    {
        $rules = [
            'kode_penyakit' => 'required|string|max:50|unique:penyakits,kode_penyakit,' . ($this->editingPenyakit ? $this->editingPenyakit->id : 'NULL'),
            'nama_penyakit' => 'required|string|max:255',
            'is_menular' => 'required|boolean',
            'solusi_pencegahan' => 'nullable|string',
        ];

        $validated = $this->validate($rules);

        if ($this->editingPenyakit) {
            $this->editingPenyakit->update($validated);
            Flux::toast(variant: 'success', text: __('Penyakit berhasil diperbarui.'));
        } else {
            Penyakit::create($validated);
            Flux::toast(variant: 'success', text: __('Penyakit baru berhasil ditambahkan.'));
        }

        $this->modal('penyakit-modal')->close();
    }

    public function deletePenyakit(int $id): void
    {
        Penyakit::findOrFail($id)->delete();
        Flux::toast(variant: 'success', text: __('Penyakit berhasil dihapus.'));
    }
};
?>

<div class="flex h-full w-full flex-1 flex-col gap-6 p-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl" level="1">{{ __('Data Penyakit') }}</flux:heading>
            <flux:text>{{ __('Kelola data penyakit untuk target klasifikasi dan konfigurasi EWS.') }}</flux:text>
        </div>
        <flux:button icon="plus" variant="primary" wire:click="openCreateModal">{{ __('Tambah Penyakit') }}</flux:button>
    </div>

    <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
        <flux:input class="flex-1" wire:model.live="search" placeholder="Cari penyakit berdasarkan kode atau nama..." icon="magnifying-glass" />
    </div>

    <flux:card class="overflow-x-auto p-0">
        <flux:table :paginate="$this->penyakits">
            <flux:table.columns>
                <flux:table.column class="pl-4">{{ __('Kode') }}</flux:table.column>
                <flux:table.column>{{ __('Nama Penyakit') }}</flux:table.column>
                <flux:table.column>{{ __('Tipe') }}</flux:table.column>
                <flux:table.column>{{ __('Rekomendasi Tindakan Preventif') }}</flux:table.column>
                <flux:table.column class="w-24">{{ __('Aksi') }}</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @foreach ($this->penyakits as $penyakit)
                <flux:table.row :key="$penyakit->id">
                    <flux:table.cell class="pl-4" variant="strong">{{ $penyakit->kode_penyakit }}</flux:table.cell>
                    <flux:table.cell>{{ $penyakit->nama_penyakit }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:badge size="sm" :color="$penyakit->is_menular ? 'red' : 'zinc'">
                            {{ $penyakit->is_menular ? __('Menular (EWS)') : __('Tidak Menular') }}
                        </flux:badge>
                    </flux:table.cell>
                    <flux:table.cell class="max-w-xs truncate">
                        {{ $penyakit->solusi_pencegahan ?: '—' }}
                    </flux:table.cell>
                    <flux:table.cell class="flex gap-2">
                        <flux:button variant="ghost" icon="pencil-square" size="sm" wire:click="editPenyakit({{ $penyakit->id }})" />
                        <flux:button variant="ghost" icon="trash" size="sm" class="text-red-500 hover:text-red-600"
                            wire:confirm="Apakah Anda yakin ingin menghapus penyakit ini?"
                            wire:click="deletePenyakit({{ $penyakit->id }})" />
                    </flux:table.cell>
                </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    </flux:card>

    <!-- Modal Form Penyakit -->
    <flux:modal name="penyakit-modal" class="md:w-96">
        <form wire:submit.prevent="savePenyakit" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ $editingPenyakit ? __('Edit Penyakit') : __('Tambah Penyakit') }}</flux:heading>
                <flux:text class="mt-2">{{ __('Lengkapi parameter data penyakit di bawah ini.') }}</flux:text>
            </div>

            <flux:input label="{{ __('Kode Penyakit') }}" wire:model="kode_penyakit" placeholder="Contoh: P01" required />
            <flux:input label="{{ __('Nama Penyakit') }}" wire:model="nama_penyakit" placeholder="Contoh: Scabies" required />

            <flux:checkbox label="{{ __('Penyakit Menular (Trigger Early Warning System)') }}" wire:model="is_menular" />

            <flux:textarea label="{{ __('Rekomendasi Tindakan Preventif') }}" wire:model="solusi_pencegahan" placeholder="Contoh: Karantina kamar, jemur kasur, bersihkan pakaian dengan air panas." />

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