<?php

use App\Exports\GejalaExport;
use App\Imports\GejalaImport;
use App\Models\Gejala;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Livewire\Attributes\Title;
use Livewire\Attributes\Computed;
use Flux\Flux;
use Maatwebsite\Excel\Facades\Excel;

new #[Title('Data Gejala')] class extends Component {
    use WithFileUploads, WithPagination;

    public string $search = '';

    // Form states
    public ?Gejala $editingGejala = null;
    public string $kode_gejala = '';
    public string $nama_gejala = '';

    // Import states
    public $importFile = null;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function gejalas()
    {
        return Gejala::query()
            ->when($this->search, function ($query) {
                $query->where('nama_gejala', 'like', '%' . $this->search . '%')
                    ->orWhere('kode_gejala', 'like', '%' . $this->search . '%');
            })
            ->orderBy('kode_gejala')
            ->paginate(10);
    }

    public function openCreateModal(): void
    {
        $this->editingGejala = null;
        
        $lastGejala = Gejala::orderBy('id', 'desc')->first();
        if ($lastGejala && preg_match('/G(\d+)/', $lastGejala->kode_gejala, $matches)) {
            $nextNumber = intval($matches[1]) + 1;
            $this->kode_gejala = 'G' . str_pad((string)$nextNumber, 2, '0', STR_PAD_LEFT);
        } else {
            $this->kode_gejala = 'G01';
        }

        $this->nama_gejala = '';

        $this->modal('gejala-modal')->show();
    }

    public function editGejala(int $id): void
    {
        $this->editingGejala = Gejala::findOrFail($id);
        $this->kode_gejala = $this->editingGejala->kode_gejala;
        $this->nama_gejala = $this->editingGejala->nama_gejala;

        $this->modal('gejala-modal')->show();
    }

    public function saveGejala(): void
    {
        $rules = [
            'kode_gejala' => 'required|string|max:50|unique:gejalas,kode_gejala,' . ($this->editingGejala ? $this->editingGejala->id : 'NULL'),
            'nama_gejala' => 'required|string|max:255',
        ];

        $validated = $this->validate($rules);

        if ($this->editingGejala) {
            $this->editingGejala->update($validated);
            Flux::toast(variant: 'success', text: __('Gejala berhasil diperbarui.'));
        } else {
            Gejala::create($validated);
            Flux::toast(variant: 'success', text: __('Gejala baru berhasil ditambahkan.'));
        }

        $this->modal('gejala-modal')->close();
    }

    public function deleteGejala(int $id): void
    {
        Gejala::findOrFail($id)->delete();
        Flux::toast(variant: 'success', text: __('Gejala berhasil dihapus.'));
    }

    public function downloadTemplate()
    {
        return Excel::download(new GejalaExport(template: true), 'template-gejala.xlsx');
    }

    public function exportExcel()
    {
        return Excel::download(new GejalaExport(), 'data-gejala-' . now()->format('Ymd_His') . '.xlsx');
    }

    public function openImportModal(): void
    {
        $this->importFile = null;
        $this->resetErrorBag('importFile');
        $this->modal('gejala-import-modal')->show();
    }

    public function importExcel(): void
    {
        $this->validate([
            'importFile' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        $import = new GejalaImport();
        Excel::import($import, $this->importFile->getRealPath());

        $failures = $import->failures();

        if ($failures->isEmpty()) {
            Flux::toast(variant: 'success', text: __('Data gejala berhasil diimpor.'));
        } else {
            Flux::toast(variant: 'warning', text: __(':count baris dilewati karena tidak valid. Pastikan data sesuai format template.', ['count' => $failures->count()]));
        }

        $this->reset('importFile');
        $this->modal('gejala-import-modal')->close();
    }
};
?>

<div class="flex h-full w-full flex-1 flex-col gap-6 p-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl" level="1">{{ __('Data Gejala') }}</flux:heading>
            <flux:text>{{ __('Kelola parameter gejala klinis untuk diagnosa Naive Bayes.') }}</flux:text>
        </div>
        <div class="flex items-center gap-2">
            <flux:button icon="document-arrow-down" variant="ghost" wire:click="downloadTemplate">{{ __('Unduh Template') }}</flux:button>
            <flux:button icon="arrow-up-tray" variant="ghost" wire:click="openImportModal">{{ __('Import') }}</flux:button>
            <flux:button icon="arrow-down-tray" variant="ghost" wire:click="exportExcel">{{ __('Export') }}</flux:button>
            <flux:button icon="plus" variant="primary" wire:click="openCreateModal">{{ __('Tambah Gejala') }}</flux:button>
        </div>
    </div>

    <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
        <flux:input class="flex-1" wire:model.live="search" placeholder="Cari gejala berdasarkan kode atau nama..." icon="magnifying-glass" />
    </div>

    <flux:card class="overflow-x-auto p-0">
        <flux:table :paginate="$this->gejalas">
            <flux:table.columns>
                <flux:table.column class="pl-4">{{ __('Kode') }}</flux:table.column>
                <flux:table.column>{{ __('Nama Gejala') }}</flux:table.column>
                <flux:table.column class="w-24">{{ __('Aksi') }}</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @foreach ($this->gejalas as $gejala)
                    <flux:table.row :key="$gejala->id">
                        <flux:table.cell class="pl-4" variant="strong">{{ $gejala->kode_gejala }}</flux:table.cell>
                        <flux:table.cell>{{ $gejala->nama_gejala }}</flux:table.cell>
                        <flux:table.cell class="flex gap-2">
                            <flux:button variant="ghost" icon="pencil-square" size="sm" wire:click="editGejala({{ $gejala->id }})" />
                            <flux:button variant="ghost" icon="trash" size="sm" class="text-red-500 hover:text-red-600" 
                                wire:confirm="Apakah Anda yakin ingin menghapus gejala ini?" 
                                wire:click="deleteGejala({{ $gejala->id }})" />
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    </flux:card>

    <!-- Modal Form Gejala -->
    <flux:modal name="gejala-modal" class="md:w-96">
        <form wire:submit.prevent="saveGejala" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ $editingGejala ? __('Edit Gejala') : __('Tambah Gejala') }}</flux:heading>
                <flux:text class="mt-2">{{ __('Lengkapi informasi parameter gejala klinis.') }}</flux:text>
            </div>

            <flux:input label="{{ __('Kode Gejala') }}" wire:model="kode_gejala" placeholder="Contoh: G01" required />
            <flux:input label="{{ __('Nama Gejala') }}" wire:model="nama_gejala" placeholder="Contoh: Demam Tinggi" required />

            <div class="flex gap-2">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Batal') }}</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">{{ __('Simpan') }}</flux:button>
            </div>
        </form>
    </flux:modal>

    <!-- Modal Import Gejala -->
    <flux:modal name="gejala-import-modal" class="md:w-[480px]">
        <form wire:submit.prevent="importExcel" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Import Data Gejala') }}</flux:heading>
                <flux:text class="mt-2">
                    {{ __('Unggah file Excel sesuai format template.') }}
                    <flux:link href="#" wire:click.prevent="downloadTemplate">{{ __('Unduh template di sini') }}</flux:link>.
                </flux:text>
            </div>

            <div>
                <flux:input type="file" label="{{ __('File Excel') }}" wire:model="importFile" accept=".xlsx,.xls,.csv" />
                @error('importFile') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
            </div>

            <div class="flex gap-2">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Batal') }}</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="importExcel">{{ __('Import') }}</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
