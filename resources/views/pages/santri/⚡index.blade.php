<?php

use App\Exports\SantriExport;
use App\Imports\SantriImport;
use App\Models\Santri;
use App\Models\Kamar;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Livewire\Attributes\Title;
use Livewire\Attributes\Computed;
use Flux\Flux;
use Maatwebsite\Excel\Facades\Excel;

new #[Title('Data Santri')] class extends Component {
    use WithFileUploads, WithPagination;

    public string $search = '';
    public string $filterKamar = '';

    // Form states
    public ?Santri $editingSantri = null;
    public string $nis = '';
    public string $nama = '';
    public string $gender = 'L';
    public ?int $kamar_id = null;

    // Import states
    public $importFile = null;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilterKamar(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function santris()
    {
        return Santri::query()
            ->with('kamar.wilayah')
            ->when($this->search, function ($query) {
                $query->where('nama', 'like', '%' . $this->search . '%')
                    ->orWhere('nis', 'like', '%' . $this->search . '%');
            })
            ->when($this->filterKamar, fn($query) => $query->where('kamar_id', $this->filterKamar))
            ->orderBy('nama')
            ->paginate(10);
    }

    #[Computed]
    public function kamarOptions()
    {
        return Kamar::with('wilayah')->orderBy('wilayah_id')->orderBy('blok')->orderBy('nama_kamar')->get();
    }

    public function openCreateModal(): void
    {
        $this->editingSantri = null;
        $this->nis = '';
        $this->nama = '';
        $this->gender = 'L';
        $this->kamar_id = null;

        $this->modal('santri-modal')->show();
    }

    public function editSantri(int $id): void
    {
        $this->editingSantri = Santri::findOrFail($id);
        $this->nis = $this->editingSantri->nis;
        $this->nama = $this->editingSantri->nama;
        $this->gender = $this->editingSantri->gender;
        $this->kamar_id = $this->editingSantri->kamar_id;

        $this->modal('santri-modal')->show();
    }

    public function saveSantri(): void
    {
        $rules = [
            'nis' => 'required|string|max:50|unique:santris,nis,' . ($this->editingSantri ? $this->editingSantri->id : 'NULL'),
            'nama' => 'required|string|max:255',
            'gender' => 'required|in:L,P',
            'kamar_id' => 'required|exists:kamars,id',
        ];

        $validated = $this->validate($rules);

        if ($this->editingSantri) {
            $this->editingSantri->update($validated);
            Flux::toast(variant: 'success', text: __('Data santri berhasil diperbarui.'));
        } else {
            Santri::create($validated);
            Flux::toast(variant: 'success', text: __('Santri baru berhasil ditambahkan.'));
        }

        $this->modal('santri-modal')->close();
    }

    public function deleteSantri(int $id): void
    {
        Santri::findOrFail($id)->delete();
        Flux::toast(variant: 'success', text: __('Data santri berhasil dihapus.'));
    }

    public function downloadTemplate()
    {
        return Excel::download(new SantriExport(template: true), 'template-santri.xlsx');
    }

    public function exportExcel()
    {
        return Excel::download(new SantriExport(), 'data-santri-' . now()->format('Ymd_His') . '.xlsx');
    }

    public function openImportModal(): void
    {
        $this->importFile = null;
        $this->resetErrorBag('importFile');
        $this->modal('santri-import-modal')->show();
    }

    public function importExcel(): void
    {
        $this->validate([
            'importFile' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        $import = new SantriImport();
        Excel::import($import, $this->importFile->getRealPath());

        $failures = $import->failures();

        if ($failures->isEmpty()) {
            Flux::toast(variant: 'success', text: __('Data santri berhasil diimpor.'));
        } else {
            Flux::toast(variant: 'warning', text: __(':count baris dilewati karena tidak valid. Pastikan wilayah, blok, dan nama kamar pada file sudah sesuai data yang ada.', ['count' => $failures->count()]));
        }

        $this->reset('importFile');
        $this->modal('santri-import-modal')->close();
    }
};
?>

<div class="flex h-full w-full flex-1 flex-col gap-6 p-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl" level="1">{{ __('Data Santri') }}</flux:heading>
            <flux:text>{{ __('Kelola data biodata santri beserta penempatan kamar.') }}</flux:text>
        </div>
        <div class="flex items-center gap-2">
            <flux:button icon="document-arrow-down" variant="ghost" wire:click="downloadTemplate">{{ __('Unduh Template') }}</flux:button>
            <flux:button icon="arrow-up-tray" variant="ghost" wire:click="openImportModal">{{ __('Import') }}</flux:button>
            <flux:button icon="arrow-down-tray" variant="ghost" wire:click="exportExcel">{{ __('Export') }}</flux:button>
            <flux:button icon="plus" variant="primary" wire:click="openCreateModal">{{ __('Tambah Santri') }}</flux:button>
        </div>
    </div>

    <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
        <flux:input class="flex-1" wire:model.live="search" placeholder="Cari santri berdasarkan NIS atau nama..." icon="magnifying-glass" />
        
        <flux:select wire:model.live="filterKamar" placeholder="Semua Kamar" class="w-full sm:w-64">
            <option value="">Semua Kamar</option>
            @foreach ($this->kamarOptions as $option)
                <option value="{{ $option->id }}">{{ $option->wilayah->nama_wilayah }} - {{ $option->blok }} - {{ $option->nama_kamar }}</option>
            @endforeach
        </flux:select>
    </div>

    <flux:card class="overflow-x-auto p-0">
        <flux:table :paginate="$this->santris">
            <flux:table.columns>
                <flux:table.column class="pl-4">{{ __('NIS') }}</flux:table.column>
                <flux:table.column>{{ __('Nama Lengkap') }}</flux:table.column>
                <flux:table.column>{{ __('Gender') }}</flux:table.column>
                <flux:table.column>{{ __('Kamar') }}</flux:table.column>
                <flux:table.column class="w-24">{{ __('Aksi') }}</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @foreach ($this->santris as $santri)
                    <flux:table.row :key="$santri->id">
                        <flux:table.cell class="pl-4">{{ $santri->nis }}</flux:table.cell>
                        <flux:table.cell variant="strong">{{ $santri->nama }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge size="sm" :color="$santri->gender === 'L' ? 'blue' : 'emerald'">
                                {{ $santri->gender === 'L' ? __('Laki-laki') : __('Perempuan') }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>
                            @if($santri->kamar)
                                <flux:text size="sm">{{ $santri->kamar->wilayah->nama_wilayah }} - {{ $santri->kamar->blok }} - {{ $santri->kamar->nama_kamar }}</flux:text>
                            @else
                                <flux:text size="sm" class="text-zinc-400">—</flux:text>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell class="flex gap-2">
                            <flux:button variant="ghost" icon="pencil-square" size="sm" wire:click="editSantri({{ $santri->id }})" />
                            <flux:button variant="ghost" icon="trash" size="sm" class="text-red-500 hover:text-red-600" 
                                wire:confirm="Apakah Anda yakin ingin menghapus data santri ini?" 
                                wire:click="deleteSantri({{ $santri->id }})" />
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    </flux:card>

    <!-- Modal Form Santri -->
    <flux:modal name="santri-modal" class="md:w-96">
        <form wire:submit.prevent="saveSantri" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ $editingSantri ? __('Edit Santri') : __('Tambah Santri') }}</flux:heading>
                <flux:text class="mt-2">{{ __('Lengkapi data pribadi santri di bawah ini.') }}</flux:text>
            </div>

            <flux:input label="{{ __('NIS') }}" wire:model="nis" placeholder="Nomor Induk Santri" required />
            <flux:input label="{{ __('Nama Lengkap') }}" wire:model="nama" placeholder="Nama Lengkap" required />
            
            <flux:radio.group label="{{ __('Jenis Kelamin') }}" wire:model="gender" variant="segmented">
                <flux:radio value="L">{{ __('Laki-laki') }}</flux:radio>
                <flux:radio value="P">{{ __('Perempuan') }}</flux:radio>
            </flux:radio.group>

            <flux:select label="{{ __('Kamar') }}" wire:model="kamar_id" required>
                <option value="">{{ __('Pilih Kamar') }}</option>
                @foreach ($this->kamarOptions as $option)
                    <option value="{{ $option->id }}">{{ $option->wilayah->nama_wilayah }} - {{ $option->blok }} - {{ $option->nama_kamar }}</option>
                @endforeach
            </flux:select>

            <div class="flex gap-2">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Batal') }}</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">{{ __('Simpan') }}</flux:button>
            </div>
        </form>
    </flux:modal>

    <!-- Modal Import Santri -->
    <flux:modal name="santri-import-modal" class="md:w-[480px]">
        <form wire:submit.prevent="importExcel" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Import Data Santri') }}</flux:heading>
                <flux:text class="mt-2">
                    {{ __('Unggah file Excel sesuai format template. Pastikan Wilayah, Blok, dan Nama Kamar cocok dengan data yang sudah ada.') }}
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
