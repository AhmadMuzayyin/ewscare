<?php

use App\Models\Wilayah;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Title;
use Livewire\Attributes\Computed;
use Flux\Flux;

new #[Title('Data Wilayah')] class extends Component {
    use WithPagination;

    public string $search = '';

    // Form states
    public ?Wilayah $editingWilayah = null;
    public string $nama_wilayah = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function wilayahs()
    {
        return Wilayah::query()
            ->withCount('kamars')
            ->when($this->search, function ($query) {
                $query->where('nama_wilayah', 'like', '%' . $this->search . '%');
            })
            ->orderBy('nama_wilayah')
            ->paginate(10);
    }

    public function openCreateModal(): void
    {
        $this->editingWilayah = null;
        $this->nama_wilayah = '';

        $this->modal('wilayah-modal')->show();
    }

    public function editWilayah(int $id): void
    {
        $this->editingWilayah = Wilayah::findOrFail($id);
        $this->nama_wilayah = $this->editingWilayah->nama_wilayah;

        $this->modal('wilayah-modal')->show();
    }

    public function saveWilayah(): void
    {
        $rules = [
            'nama_wilayah' => 'required|string|max:255|unique:wilayahs,nama_wilayah,' . ($this->editingWilayah ? $this->editingWilayah->id : 'NULL'),
        ];

        $validated = $this->validate($rules);

        if ($this->editingWilayah) {
            $this->editingWilayah->update($validated);
            Flux::toast(variant: 'success', text: __('Wilayah berhasil diperbarui.'));
        } else {
            Wilayah::create($validated);
            Flux::toast(variant: 'success', text: __('Wilayah baru berhasil ditambahkan.'));
        }

        $this->modal('wilayah-modal')->close();
    }

    public function deleteWilayah(int $id): void
    {
        $wilayah = Wilayah::withCount('kamars')->findOrFail($id);

        if ($wilayah->kamars_count > 0) {
            Flux::toast(variant: 'danger', text: __('Wilayah tidak dapat dihapus karena masih memiliki data kamar.'));
            return;
        }

        $wilayah->delete();
        Flux::toast(variant: 'success', text: __('Wilayah berhasil dihapus.'));
    }
};
?>

<div class="flex h-full w-full flex-1 flex-col gap-6 p-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl" level="1">{{ __('Data Wilayah') }}</flux:heading>
            <flux:text>{{ __('Kelola master wilayah sebagai induk pembagian kamar santri.') }}</flux:text>
        </div>
        <flux:button icon="plus" variant="primary" wire:click="openCreateModal">{{ __('Tambah Wilayah') }}</flux:button>
    </div>

    <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
        <flux:input class="flex-1" wire:model.live="search" placeholder="Cari nama wilayah..." icon="magnifying-glass" />
    </div>

    <flux:card class="overflow-x-auto p-0">
        <flux:table :paginate="$this->wilayahs">
            <flux:table.columns>
                <flux:table.column class="pl-4">{{ __('Nama Wilayah') }}</flux:table.column>
                <flux:table.column>{{ __('Jumlah Kamar') }}</flux:table.column>
                <flux:table.column class="w-24">{{ __('Aksi') }}</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @foreach ($this->wilayahs as $wilayah)
                    <flux:table.row :key="$wilayah->id">
                        <flux:table.cell class="pl-4" variant="strong">{{ $wilayah->nama_wilayah }}</flux:table.cell>
                        <flux:table.cell>{{ $wilayah->kamars_count }} {{ __('Kamar') }}</flux:table.cell>
                        <flux:table.cell class="flex gap-2">
                            <flux:button variant="ghost" icon="pencil-square" size="sm" wire:click="editWilayah({{ $wilayah->id }})" />
                            <flux:button variant="ghost" icon="trash" size="sm" class="text-red-500 hover:text-red-600"
                                wire:confirm="Apakah Anda yakin ingin menghapus wilayah ini?"
                                wire:click="deleteWilayah({{ $wilayah->id }})" />
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    </flux:card>

    <!-- Modal Form Wilayah -->
    <flux:modal name="wilayah-modal" class="md:w-96">
        <form wire:submit.prevent="saveWilayah" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ $editingWilayah ? __('Edit Wilayah') : __('Tambah Wilayah') }}</flux:heading>
                <flux:text class="mt-2">{{ __('Lengkapi informasi wilayah di bawah ini.') }}</flux:text>
            </div>

            <flux:input label="{{ __('Nama Wilayah') }}" wire:model="nama_wilayah" placeholder="Contoh: Wilayah 1" required />

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
