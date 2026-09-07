<?php

use App\Exports\KamarExport;
use App\Imports\KamarImport;
use App\Models\Kamar;
use App\Models\Wilayah;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Livewire\Attributes\Title;
use Livewire\Attributes\Computed;
use Flux\Flux;
use Maatwebsite\Excel\Facades\Excel;

new #[Title('Data Kamar')] class extends Component {
    use WithFileUploads, WithPagination;

    public string $search = '';
    public string $filterWilayah = '';
    public string $filterBlok = '';

    // Form states
    public ?Kamar $editingKamar = null;
    public ?int $wilayah_id = null;
    public string $blok = '';
    public string $nama_kamar = '';
    public int $kapasitas = 10;

    // Import states
    public $importFile = null;

    public function updatedSearch(): void
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

    #[Computed]
    public function kamars()
    {
        return Kamar::query()
            ->with('wilayah')
            ->when($this->search, function ($query) {
                $query->where('nama_kamar', 'like', '%' . $this->search . '%')
                    ->orWhere('blok', 'like', '%' . $this->search . '%');
            })
            ->when($this->filterWilayah, fn($query) => $query->where('wilayah_id', $this->filterWilayah))
            ->when($this->filterBlok, fn($query) => $query->where('blok', $this->filterBlok))
            ->orderBy('wilayah_id')
            ->orderBy('blok')
            ->orderBy('nama_kamar')
            ->paginate(10);
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

    public function openCreateModal(): void
    {
        $this->editingKamar = null;
        $this->wilayah_id = null;
        $this->blok = '';
        $this->nama_kamar = '';
        $this->kapasitas = 10;

        $this->modal('kamar-modal')->show();
    }

    public function editKamar(int $id): void
    {
        $this->editingKamar = Kamar::findOrFail($id);
        $this->wilayah_id = $this->editingKamar->wilayah_id;
        $this->blok = $this->editingKamar->blok;
        $this->nama_kamar = $this->editingKamar->nama_kamar;
        $this->kapasitas = $this->editingKamar->kapasitas;

        $this->modal('kamar-modal')->show();
    }

    public function saveKamar(): void
    {
        $rules = [
            'wilayah_id' => 'required|exists:wilayahs,id',
            'blok' => 'required|string|max:255',
            'nama_kamar' => 'required|string|max:255',
            'kapasitas' => 'required|integer|min:1',
        ];

        $validated = $this->validate($rules);

        if ($this->editingKamar) {
            $this->editingKamar->update($validated);
            Flux::toast(variant: 'success', text: __('Kamar berhasil diperbarui.'));
        } else {
            Kamar::create($validated);
            Flux::toast(variant: 'success', text: __('Kamar baru berhasil ditambahkan.'));
        }

        $this->modal('kamar-modal')->close();
    }

    public function deleteKamar(int $id): void
    {
        Kamar::findOrFail($id)->delete();
        Flux::toast(variant: 'success', text: __('Kamar berhasil dihapus.'));
    }

    public function downloadTemplate()
    {
        return Excel::download(new KamarExport(template: true), 'template-kamar.xlsx');
    }

    public function exportExcel()
    {
        return Excel::download(new KamarExport(), 'data-kamar-' . now()->format('Ymd_His') . '.xlsx');
    }

    public function openImportModal(): void
    {
        $this->importFile = null;
        $this->resetErrorBag('importFile');
        $this->modal('kamar-import-modal')->show();
    }

    public function importExcel(): void
    {
        $this->validate([
            'importFile' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        $import = new KamarImport();
        Excel::import($import, $this->importFile->getRealPath());

        $failures = $import->failures();

        if ($failures->isEmpty()) {
            Flux::toast(variant: 'success', text: __('Data kamar berhasil diimpor.'));
        } else {
            Flux::toast(variant: 'warning', text: __(':count baris dilewati karena tidak valid. Pastikan nama wilayah pada file sudah sesuai data yang ada.', ['count' => $failures->count()]));
        }

        $this->reset('importFile');
        $this->modal('kamar-import-modal')->close();
    }
};
?>

<div class="flex h-full w-full flex-1 flex-col gap-6 p-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl" level="1">{{ __('Data Kamar') }}</flux:heading>
            <flux:text>{{ __('Kelola data kamar dan pembagian wilayah/blok santri.') }}</flux:text>
        </div>
        <div class="flex items-center gap-2">
            <flux:button icon="document-arrow-down" variant="ghost" wire:click="downloadTemplate">{{ __('Unduh Template') }}</flux:button>
            <flux:button icon="arrow-up-tray" variant="ghost" wire:click="openImportModal">{{ __('Import') }}</flux:button>
            <flux:button icon="arrow-down-tray" variant="ghost" wire:click="exportExcel">{{ __('Export') }}</flux:button>
            <flux:button icon="plus" variant="primary" wire:click="openCreateModal">{{ __('Tambah Kamar') }}</flux:button>
        </div>
    </div>

    <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
        <flux:input class="flex-1" wire:model.live="search" placeholder="Cari kamar atau blok..." icon="magnifying-glass" />
        
        <flux:select wire:model.live="filterWilayah" placeholder="Semua Wilayah" class="w-full sm:w-48">
            <option value="">Semua Wilayah</option>
            @foreach ($this->wilayahOptions as $option)
                <option value="{{ $option->id }}">{{ $option->nama_wilayah }}</option>
            @endforeach
        </flux:select>

        <flux:select wire:model.live="filterBlok" placeholder="Semua Blok" class="w-full sm:w-48">
            <option value="">Semua Blok</option>
            @foreach ($this->blokOptions as $option)
                <option value="{{ $option }}">{{ $option }}</option>
            @endforeach
        </flux:select>
    </div>

    <flux:card class="overflow-x-auto p-0">
        <flux:table :paginate="$this->kamars">
            <flux:table.columns>
                <flux:table.column class="pl-4">{{ __('Wilayah') }}</flux:table.column>
                <flux:table.column>{{ __('Blok') }}</flux:table.column>
                <flux:table.column>{{ __('Nama Kamar') }}</flux:table.column>
                <flux:table.column>{{ __('Kapasitas') }}</flux:table.column>
                <flux:table.column class="w-24">{{ __('Aksi') }}</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @foreach ($this->kamars as $kamar)
                    <flux:table.row :key="$kamar->id">
                        <flux:table.cell class="pl-4">{{ $kamar->wilayah->nama_wilayah }}</flux:table.cell>
                        <flux:table.cell>{{ $kamar->blok }}</flux:table.cell>
                        <flux:table.cell variant="strong">{{ $kamar->nama_kamar }}</flux:table.cell>
                        <flux:table.cell>{{ $kamar->kapasitas }} {{ __('Santri') }}</flux:table.cell>
                        <flux:table.cell class="flex gap-2">
                            <flux:button variant="ghost" icon="pencil-square" size="sm" wire:click="editKamar({{ $kamar->id }})" />
                            <flux:button variant="ghost" icon="trash" size="sm" class="text-red-500 hover:text-red-600" 
                                wire:confirm="Apakah Anda yakin ingin menghapus kamar ini?" 
                                wire:click="deleteKamar({{ $kamar->id }})" />
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    </flux:card>

    <!-- Modal Form Kamar -->
    <flux:modal name="kamar-modal" class="md:w-96">
        <form wire:submit.prevent="saveKamar" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ $editingKamar ? __('Edit Kamar') : __('Tambah Kamar') }}</flux:heading>
                <flux:text class="mt-2">{{ __('Lengkapi informasi kamar di bawah ini.') }}</flux:text>
            </div>

            <flux:select label="{{ __('Wilayah') }}" wire:model="wilayah_id" required>
                <option value="">{{ __('Pilih Wilayah') }}</option>
                @foreach ($this->wilayahOptions as $option)
                    <option value="{{ $option->id }}">{{ $option->nama_wilayah }}</option>
                @endforeach
            </flux:select>

            <flux:input label="{{ __('Blok') }}" wire:model="blok" placeholder="Contoh: Blok A, DKL, BPBAE" required />
            <flux:input label="{{ __('Nama Kamar') }}" wire:model="nama_kamar" placeholder="Contoh: Kamar 01" required />
            <flux:input label="{{ __('Kapasitas') }}" type="number" wire:model="kapasitas" min="1" required />

            <div class="flex gap-2">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Batal') }}</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">{{ __('Simpan') }}</flux:button>
            </div>
        </form>
    </flux:modal>

    <!-- Modal Import Kamar -->
    <flux:modal name="kamar-import-modal" class="md:w-[480px]">
        <form wire:submit.prevent="importExcel" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Import Data Kamar') }}</flux:heading>
                <flux:text class="mt-2">
                    {{ __('Unggah file Excel sesuai format template. Pastikan kolom Wilayah cocok dengan data Wilayah yang sudah ada.') }}
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
