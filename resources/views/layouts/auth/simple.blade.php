<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head', ['title' => $title ?? 'Masuk - EWSCare'])
    </head>
    <body class="min-h-screen bg-zinc-50 font-sans text-zinc-900 antialiased dark:bg-zinc-950 dark:text-zinc-100 flex flex-col justify-between selection:bg-emerald-500 selection:text-white">

        <!-- BACKGROUND ACCENT DECORATION -->
        <div class="fixed inset-0 -z-10 overflow-hidden pointer-events-none">
            <div class="absolute -top-40 -left-40 w-96 h-96 bg-emerald-500/10 dark:bg-emerald-500/15 rounded-full blur-3xl"></div>
            <div class="absolute top-1/2 -right-40 w-96 h-96 bg-teal-500/10 dark:bg-teal-500/15 rounded-full blur-3xl"></div>
            <div class="absolute -bottom-40 left-1/3 w-96 h-96 bg-cyan-500/10 dark:bg-cyan-500/15 rounded-full blur-3xl"></div>
        </div>

        <div class="flex min-h-svh flex-col items-center justify-center p-6 md:p-10">
            <!-- Back to Home Link -->
            <div class="w-full max-w-md mb-4 flex items-center justify-between">
                <a href="{{ route('home') }}" class="inline-flex items-center text-xs font-medium text-zinc-500 hover:text-emerald-500 dark:text-zinc-400 dark:hover:text-emerald-400 transition-colors">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Kembali ke Beranda
                </a>
                <span class="text-[11px] font-semibold text-emerald-600 dark:text-emerald-400 bg-emerald-500/10 px-2.5 py-0.5 rounded-full border border-emerald-500/20">
                    EWSCare Auth
                </span>
            </div>

            <!-- Card Container -->
            <div class="w-full max-w-md bg-white/80 dark:bg-zinc-900/90 border border-zinc-200/80 dark:border-zinc-800/80 rounded-2xl p-8 sm:p-10 shadow-2xl shadow-emerald-500/5 backdrop-blur-xl flex flex-col gap-6">
                <!-- App Brand Logo Header -->
                <div class="flex flex-col items-center text-center gap-2">
                    <a href="{{ route('home') }}" class="group flex flex-col items-center gap-2">
                        <div class="w-12 h-12 rounded-xl bg-emerald-500/10 dark:bg-emerald-500/20 border border-emerald-500/30 flex items-center justify-center text-emerald-600 dark:text-emerald-400 group-hover:scale-105 transition-transform">
                            <svg class="w-7 h-7 animate-pulse" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.684a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                            </svg>
                        </div>
                        <span class="font-extrabold text-xl tracking-tight text-zinc-900 dark:text-white">EWS<span class="text-emerald-500">CARE</span></span>
                    </a>
                </div>

                {{ $slot }}
            </div>

            <!-- Security Footer Note -->
            <div class="mt-6 text-center text-xs text-zinc-500 dark:text-zinc-400 flex items-center gap-1.5 justify-center">
                <svg class="w-3.5 h-3.5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
                <span>Akses Terenkripsi &amp; Sistem Peringatan Dini Kesehatan Santri</span>
            </div>
        </div>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
