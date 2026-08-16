<?php

use App\Models\Santri;
use App\Models\Kamar;
use App\Models\Penyakit;
use App\Models\EwsAlert;
use App\Models\RiwayatKesehatan;
use Livewire\Component;
use Livewire\Attributes\Title;
use Livewire\Attributes\Computed;
use Flux\Flux;

new #[Title('Dashboard EWS Spasial')] class extends Component {
    public ?int $selectedKamarId = null;
    public ?Kamar $selectedKamar = null;
    public $activeCasesInKamar = [];

    #[Computed]
    public function stats(): array
    {
        return [
            'total_santri' => Santri::count(),
            'total_kamar' => Kamar::count(),
            'total_kasus' => RiwayatKesehatan::where('tanggal_periksa', '>=', now()->subDays(7)->toDateString())->count(),
            'total_alert' => EwsAlert::where('status', 'Aktif')->count(),
        ];
    }

    #[Computed]
    public function ewsAlerts()
    {
        return EwsAlert::with(['kamar', 'penyakit'])->where('status', 'Aktif')->latest()->get();
    }

    // Active cases in the last 7 days grouped by kamar_id
    #[Computed]
    public function kamarStatus(): array
    {
        $recentCases = RiwayatKesehatan::with('penyakit')
            ->where('tanggal_periksa', '>=', now()->subDays(7)->toDateString())
            ->get();

        $status = [];

        foreach ($recentCases as $case) {
            if (!isset($status[$case->kamar_id])) {
                $status[$case->kamar_id] = [
                    'has_menular' => false,
                    'total_kasus' => 0,
                    'penyakit_list' => [],
                ];
            }

            $status[$case->kamar_id]['total_kasus']++;
            if ($case->penyakit->is_menular) {
                $status[$case->kamar_id]['has_menular'] = true;
            }
            $status[$case->kamar_id]['penyakit_list'][] = $case->penyakit->nama_penyakit;
        }

        return $status;
    }

    // Map out the structured layout
    #[Computed]
    public function mapStructure(): array
    {
        $kamars = Kamar::orderBy('wilayah')->orderBy('blok')->orderBy('nama_kamar')->get();
        $structure = [];

        foreach ($kamars as $kamar) {
            $structure[$kamar->wilayah][$kamar->blok][] = $kamar;
        }

        return $structure;
    }

    public function selectKamar(int $id): void
    {
        $this->selectedKamarId = $id;
        $this->selectedKamar = Kamar::findOrFail($id);
        
        $this->activeCasesInKamar = RiwayatKesehatan::with(['santri', 'penyakit'])
            ->where('kamar_id', $id)
            ->where('tanggal_periksa', '>=', now()->subDays(7)->toDateString())
            ->get();

        $this->modal('kamar-detail-modal')->show();
    }

    public function closeAlert(int $alertId): void
    {
        EwsAlert::findOrFail($alertId)->update(['status' => 'Tertangani']);
        Flux::toast(variant: 'success', text: __('Peringatan dinilai sudah tertangani.'));
    }
};
?>

<div class="flex h-full w-full flex-1 flex-col gap-6 p-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between">
        <div>
            <flux:heading size="xl" level="1">{{ __('Dashboard Early Warning System (EWS)') }}</flux:heading>
            <flux:text>{{ __('Peta spasial sebaran penyakit menular dan pemantauan wabah di Pondok Pesantren Annuqayah Lubangsa.') }}</flux:text>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <flux:card class="flex items-center gap-4">
            <div class="p-3 bg-blue-100 text-blue-600 dark:bg-blue-950/50 dark:text-blue-400 rounded-lg">
                <flux:icon name="users" class="size-6" />
            </div>
            <div>
                <flux:text size="sm" class="text-zinc-500">{{ __('Total Santri') }}</flux:text>
                <flux:heading size="lg">{{ $this->stats['total_santri'] }}</flux:heading>
            </div>
        </flux:card>

        <flux:card class="flex items-center gap-4">
            <div class="p-3 bg-purple-100 text-purple-600 dark:bg-purple-950/50 dark:text-purple-400 rounded-lg">
                <flux:icon name="home" class="size-6" />
            </div>
            <div>
                <flux:text size="sm" class="text-zinc-500">{{ __('Total Kamar') }}</flux:text>
                <flux:heading size="lg">{{ $this->stats['total_kamar'] }}</flux:heading>
            </div>
        </flux:card>

        <flux:card class="flex items-center gap-4">
            <div class="p-3 bg-amber-100 text-amber-600 dark:bg-amber-950/50 dark:text-amber-400 rounded-lg">
                <flux:icon name="beaker" class="size-6" />
            </div>
            <div>
                <flux:text size="sm" class="text-zinc-500">{{ __('Kasus Baru (7 Hari)') }}</flux:text>
                <flux:heading size="lg">{{ $this->stats['total_kasus'] }}</flux:heading>
            </div>
        </flux:card>

        <flux:card class="flex items-center gap-4 {{ $this->stats['total_alert'] > 0 ? 'border-red-500 dark:border-red-600 bg-red-50/20' : '' }}">
            <div class="p-3 {{ $this->stats['total_alert'] > 0 ? 'bg-red-100 text-red-600 dark:bg-red-950/50 dark:text-red-400' : 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400' }} rounded-lg">
                <flux:icon name="exclamation-triangle" class="size-6" />
            </div>
            <div>
                <flux:text size="sm" class="text-zinc-500">{{ __('Peringatan Wabah Aktif') }}</flux:text>
                <flux:heading size="lg" class="{{ $this->stats['total_alert'] > 0 ? 'text-red-600 dark:text-red-400' : '' }}">
                    {{ $this->stats['total_alert'] }}
                </flux:heading>
            </div>
        </flux:card>
    </div>

    <!-- Active Alerts Banner -->
    @if($this->ewsAlerts->isNotEmpty())
        <div class="space-y-3">
            <flux:heading size="md" class="text-red-600 dark:text-red-400 flex items-center gap-2">
                <flux:icon name="exclamation-circle" class="size-5" />
                {{ __('Peringatan Dini Outbreak Aktif') }}
            </flux:heading>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach($this->ewsAlerts as $alert)
                    <div wire:key="alert-{{ $alert->id }}" class="flex items-start justify-between p-4 bg-red-50 border border-red-200 dark:bg-red-950/10 dark:border-red-900/50 rounded-xl">
                        <div class="space-y-1">
                            <flux:heading size="sm" class="text-red-700 dark:text-red-400">
                                {{ __('Waspada Outbreak') }} {{ $alert->penyakit->nama_penyakit }}
                            </flux:heading>
                            <flux:text size="sm" class="text-red-600 dark:text-red-300">
                                {{ __('Ditemukan') }} <strong>{{ $alert->jumlah_kasus }} kasus</strong> {{ __('di') }} <strong>{{ $alert->blok }} - {{ $alert->kamar->nama_kamar }} ({{ $alert->wilayah }})</strong>.
                            </flux:text>
                            @if($alert->penyakit->solusi_pencegahan)
                                <div class="text-xs text-red-500 dark:text-red-400 mt-2 bg-white dark:bg-zinc-900 p-2 rounded border border-red-100 dark:border-red-950/80">
                                    <strong>{{ __('Tindakan:') }}</strong> {{ $alert->penyakit->solusi_pencegahan }}
                                </div>
                            @endif
                        </div>
                        <flux:button variant="ghost" size="sm" class="text-red-600 dark:text-red-400 hover:bg-red-100 dark:hover:bg-red-950/40" 
                            wire:click="closeAlert({{ $alert->id }})">
                            {{ __('Tandai Selesai') }}
                        </flux:button>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Spatial Mapping Section -->
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <div>
                <flux:heading size="lg">{{ __('Peta Klaster Spasial Kesehatan') }}</flux:heading>
                <flux:text size="sm">{{ __('Klik pada kamar untuk melihat data santri yang sedang sakit.') }}</flux:text>
            </div>
            <!-- Legend -->
            <div class="flex items-center gap-4 text-xs font-medium">
                <div class="flex items-center gap-1.5">
                    <span class="size-3.5 rounded bg-emerald-500 border border-emerald-600"></span>
                    <span>{{ __('Aman (0 Kasus)') }}</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="size-3.5 rounded bg-amber-500 border border-amber-600"></span>
                    <span>{{ __('Ada Kasus') }}</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="size-3.5 rounded bg-red-500 border border-red-600 animate-pulse"></span>
                    <span>{{ __('Bahaya/Menular') }}</span>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
            @foreach($this->mapStructure as $wilayah => $bloks)
                <flux:card wire:key="map-{{ $wilayah }}" class="space-y-6 bg-zinc-50/50 dark:bg-zinc-900/50 border border-zinc-200/60">
                    <div class="border-b border-zinc-200 dark:border-zinc-800 pb-3">
                        <flux:heading size="md">{{ $wilayah }}</flux:heading>
                    </div>

                    <div class="space-y-6">
                        @foreach($bloks as $blok => $rooms)
                            <div wire:key="blok-{{ $wilayah }}-{{ $blok }}" class="space-y-2">
                                <flux:text size="sm" class="font-semibold text-zinc-600 dark:text-zinc-400">{{ $blok }}</flux:text>
                                <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-6 lg:grid-cols-4 gap-2">
                                    @foreach($rooms as $room)
                                        @php
                                            $roomCase = $this->kamarStatus[$room->id] ?? null;
                                            $colorClass = 'bg-emerald-50 border-emerald-200 text-emerald-800 hover:bg-emerald-100 dark:bg-emerald-950/20 dark:border-emerald-900/50 dark:text-emerald-400';
                                            $badgeMarkup = null;

                                            if ($roomCase) {
                                                if ($roomCase['has_menular']) {
                                                    $colorClass = 'bg-red-500 border-red-600 text-white hover:bg-red-600 animate-pulse';
                                                    $badgeMarkup = '('.$roomCase['total_kasus'].')';
                                                } else {
                                                    $colorClass = 'bg-amber-500 border-amber-600 text-white hover:bg-amber-600';
                                                    $badgeMarkup = '('.$roomCase['total_kasus'].')';
                                                }
                                            }
                                        @endphp
                                        <button wire:key="room-{{ $room->id }}" wire:click="selectKamar({{ $room->id }})"
                                            class="p-2 border rounded-lg text-center text-xs font-semibold shadow-sm transition {{ $colorClass }}">
                                            <div>{{ str_replace('Kamar ', '', $room->nama_kamar) }}</div>
                                            @if($badgeMarkup)
                                                <div class="text-[10px] opacity-90 mt-0.5">{{ $badgeMarkup }}</div>
                                            @endif
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </flux:card>
            @endforeach
        </div>
    </div>

    <!-- Modal Kamar Detail -->
    <flux:modal name="kamar-detail-modal" class="md:w-[500px]">
        @if($selectedKamar)
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">{{ $selectedKamar->wilayah }} - {{ $selectedKamar->blok }} - {{ $selectedKamar->nama_kamar }}</flux:heading>
                    <flux:text class="mt-2">{{ __('Daftar kasus kesehatan santri aktif (7 hari terakhir) di kamar ini.') }}</flux:text>
                </div>

                <flux:separator />

                <div class="space-y-4 max-h-80 overflow-y-auto pr-1">
                    @if(count($activeCasesInKamar) > 0)
                        @foreach($activeCasesInKamar as $case)
                            <div wire:key="case-det-{{ $case->id }}" class="flex justify-between items-start p-3 bg-zinc-50 dark:bg-zinc-800 rounded-lg border border-zinc-100 dark:border-zinc-700">
                                <div>
                                    <div class="font-semibold text-sm">{{ $case->santri->nama }}</div>
                                    <div class="text-xs text-zinc-500">{{ $case->santri->nis }}</div>
                                    <div class="flex gap-1.5 mt-2">
                                        @foreach($case->gejalas as $gejala)
                                            <flux:badge size="sm" variant="outline" color="blue">{{ $gejala->nama_gejala }}</flux:badge>
                                        @endforeach
                                    </div>
                                </div>
                                <div class="text-right">
                                    <flux:badge color="{{ $case->penyakit->is_menular ? 'red' : 'zinc' }}">
                                        {{ $case->penyakit->nama_penyakit }}
                                    </flux:badge>
                                    <div class="text-[10px] text-zinc-400 mt-1.5">{{ \Carbon\Carbon::parse($case->tanggal_periksa)->diffForHumans() }}</div>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="text-center p-8 bg-zinc-50 dark:bg-zinc-800 rounded-lg border border-dashed text-zinc-400 dark:text-zinc-500">
                            <flux:icon name="check-circle" class="size-10 mx-auto text-emerald-500 mb-2" />
                            <div class="font-medium">{{ __('Kamar Ini Bersih & Sehat') }}</div>
                            <div class="text-xs mt-1">{{ __('Tidak ada santri yang dilaporkan sakit dalam 7 hari terakhir.') }}</div>
                        </div>
                    @endif
                </div>

                <div class="flex">
                    <flux:spacer />
                    <flux:modal.close>
                        <flux:button variant="ghost">{{ __('Tutup') }}</flux:button>
                    </flux:modal.close>
                </div>
            </div>
        @endif
    </flux:modal>
</div>
