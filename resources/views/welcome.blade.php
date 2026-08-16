<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head', ['title' => 'EWSCare - Early Warning System Kesehatan Santri'])
    </head>
    <body class="min-h-screen bg-zinc-50 font-sans text-zinc-900 antialiased dark:bg-zinc-950 dark:text-zinc-100 flex flex-col justify-between selection:bg-emerald-500 selection:text-white">

        <!-- BACKGROUND ACCENT DECORATION -->
        <div class="fixed inset-0 -z-10 overflow-hidden pointer-events-none">
            <div class="absolute -top-40 -left-40 w-96 h-96 bg-emerald-500/10 dark:bg-emerald-500/15 rounded-full blur-3xl"></div>
            <div class="absolute top-1/3 -right-40 w-96 h-96 bg-teal-500/10 dark:bg-teal-500/15 rounded-full blur-3xl"></div>
            <div class="absolute -bottom-40 left-1/3 w-96 h-96 bg-cyan-500/10 dark:bg-cyan-500/15 rounded-full blur-3xl"></div>
        </div>

        <!-- 1. NAVBAR -->
        <header class="sticky top-0 z-50 w-full border-b border-zinc-200/80 bg-white/80 backdrop-blur-md dark:border-zinc-800/80 dark:bg-zinc-950/80">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
                <!-- Brand Logo -->
                <a href="{{ route('home') }}" class="flex items-center gap-3 group">
                    <div class="w-10 h-10 rounded-xl bg-emerald-500/10 dark:bg-emerald-500/20 border border-emerald-500/30 flex items-center justify-center text-emerald-600 dark:text-emerald-400 group-hover:scale-105 transition-transform">
                        <svg class="w-6 h-6 animate-pulse" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.684a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                        </svg>
                    </div>
                    <div class="flex flex-col">
                        <span class="font-bold text-lg leading-none text-zinc-900 dark:text-white tracking-tight">EWS<span class="text-emerald-500">CARE</span></span>
                        <span class="text-[10px] font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Health Early Warning System</span>
                    </div>
                </a>

                <!-- Navigation Actions -->
                <div class="flex items-center gap-3">
                    @auth
                        <a href="{{ route('dashboard') }}" class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium rounded-lg text-white bg-emerald-600 hover:bg-emerald-500 transition-colors shadow-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 00-1-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                            </svg>
                            Ke Dashboard
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium rounded-lg text-white bg-emerald-600 hover:bg-emerald-500 transition-colors shadow-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                            </svg>
                            Masuk ke Sistem
                        </a>
                    @endauth
                </div>
            </div>
        </header>

        <!-- 2. HERO SECTION -->
        <main class="flex-grow flex items-center justify-center py-12 lg:py-20">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">
                    
                    <!-- Hero Content Left -->
                    <div class="lg:col-span-7 flex flex-col items-start space-y-6 text-left">
                        
                        <!-- Status Pill -->
                        <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-emerald-500/10 border border-emerald-500/20 text-emerald-600 dark:text-emerald-400 text-xs font-semibold tracking-wide">
                            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-ping"></span>
                            Early Warning System (EWS) Pesantren
                        </div>

                        <!-- Headline -->
                        <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold tracking-tight text-zinc-900 dark:text-white leading-[1.15]">
                            Deteksi Dini & Pemantauan <span class="bg-gradient-to-r from-emerald-500 via-teal-400 to-cyan-500 bg-clip-text text-transparent">Kesehatan Santri</span>
                        </h1>

                        <!-- Subtitle -->
                        <p class="text-lg sm:text-xl text-zinc-600 dark:text-zinc-400 font-normal leading-relaxed max-w-2xl">
                            Solusi pintar monitoring kondisi kesehatan santri berbasis Early Warning Score. Membantu pengelola pesantren mendeteksi gejala penyakit per kamar secara cepat, terstruktur, dan akurat.
                        </p>

                        <!-- Call to Action Buttons -->
                        <div class="flex flex-col sm:flex-row gap-4 w-full sm:w-auto pt-2">
                            @auth
                                <a href="{{ route('dashboard') }}" class="inline-flex items-center justify-center px-6 py-3.5 text-base font-semibold rounded-xl text-white bg-emerald-600 hover:bg-emerald-500 transition-all shadow-lg shadow-emerald-500/25 hover:shadow-emerald-500/40 hover:-translate-y-0.5">
                                    Buka Dashboard EWSCare
                                    <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                                    </svg>
                                </a>
                            @else
                                <a href="{{ route('login') }}" class="inline-flex items-center justify-center px-6 py-3.5 text-base font-semibold rounded-xl text-white bg-emerald-600 hover:bg-emerald-500 transition-all shadow-lg shadow-emerald-500/25 hover:shadow-emerald-500/40 hover:-translate-y-0.5">
                                    Masuk ke Aplikasi
                                    <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                                    </svg>
                                </a>
                            @endauth

                            <a href="#fitur-sistem" class="inline-flex items-center justify-center px-6 py-3.5 text-base font-semibold rounded-xl text-zinc-700 dark:text-zinc-200 bg-zinc-100 dark:bg-zinc-900 hover:bg-zinc-200 dark:hover:bg-zinc-800 border border-zinc-200 dark:border-zinc-800 transition-all">
                                Ringkasan Fitur
                            </a>
                        </div>

                        <!-- Highlights Badge Row -->
                        <div class="pt-4 grid grid-cols-3 gap-6 border-t border-zinc-200 dark:border-zinc-800/80 w-full max-w-xl">
                            <div>
                                <div class="text-2xl font-bold text-zinc-900 dark:text-white">Real-time</div>
                                <div class="text-xs text-zinc-500 dark:text-zinc-400">Peringatan Risiko EWS</div>
                            </div>
                            <div>
                                <div class="text-2xl font-bold text-zinc-900 dark:text-white">Per Kamar</div>
                                <div class="text-xs text-zinc-500 dark:text-zinc-400">Pemantauan Asrama</div>
                            </div>
                            <div>
                                <div class="text-2xl font-bold text-zinc-900 dark:text-white">Prediksi Dini</div>
                                <div class="text-xs text-zinc-500 dark:text-zinc-400">Gejala & Penyakit</div>
                            </div>
                        </div>

                    </div>

                    <!-- Hero Visual Right (Interactive Alert Card Preview) -->
                    <div class="lg:col-span-5 relative" id="fitur-sistem">
                        <div class="relative rounded-2xl bg-zinc-900/90 dark:bg-zinc-900/90 border border-zinc-800 p-6 shadow-2xl shadow-emerald-500/10 backdrop-blur-xl">
                            
                            <!-- Mockup Header -->
                            <div class="flex items-center justify-between pb-4 border-b border-zinc-800">
                                <div class="flex items-center gap-2">
                                    <div class="w-3 h-3 rounded-full bg-rose-500"></div>
                                    <div class="w-3 h-3 rounded-full bg-amber-500"></div>
                                    <div class="w-3 h-3 rounded-full bg-emerald-500"></div>
                                </div>
                                <span class="text-xs font-mono text-zinc-400">Live EWS Indicator</span>
                            </div>

                            <!-- Mockup Content Cards -->
                            <div class="mt-6 space-y-4">
                                
                                <!-- Card 1: Normal Status -->
                                <div class="p-4 rounded-xl bg-zinc-800/60 border border-zinc-700/50 flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-lg bg-emerald-500/20 flex items-center justify-center text-emerald-400">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        </div>
                                        <div>
                                            <div class="text-sm font-semibold text-white">Kamar Al-Ghazali (K-01)</div>
                                            <div class="text-xs text-zinc-400">12 Santri - Kondisi Sehat</div>
                                        </div>
                                    </div>
                                    <span class="px-2.5 py-1 rounded-full bg-emerald-500/20 text-emerald-400 text-xs font-medium">Risk Score: Low</span>
                                </div>

                                <!-- Card 2: Warning Alert -->
                                <div class="p-4 rounded-xl bg-zinc-800/60 border border-amber-500/30 flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-lg bg-amber-500/20 flex items-center justify-center text-amber-400">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                        </div>
                                        <div>
                                            <div class="text-sm font-semibold text-white">Kamar Ibnu Sina (K-04)</div>
                                            <div class="text-xs text-zinc-400">2 Santri Bergejala Demam</div>
                                        </div>
                                    </div>
                                    <span class="px-2.5 py-1 rounded-full bg-amber-500/20 text-amber-400 text-xs font-medium">Waspada EWS</span>
                                </div>

                                <!-- Card 3: Action Summary -->
                                <div class="p-4 rounded-xl bg-gradient-to-r from-emerald-900/40 to-teal-900/40 border border-emerald-500/30 flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-lg bg-emerald-500/30 flex items-center justify-center text-emerald-300">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                                        </div>
                                        <div>
                                            <div class="text-sm font-semibold text-white">Laporan Deteksi Dini</div>
                                            <div class="text-xs text-zinc-300">Rekapitulasi otomatis terintegrasi</div>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <span class="text-xs text-emerald-400 font-medium">Terverifikasi</span>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </main>

        <!-- 3. FOOTER -->
        <footer class="w-full border-t border-zinc-200/80 dark:border-zinc-800/80 bg-white/50 dark:bg-zinc-950/50 py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col md:flex-row items-center justify-between gap-4">
                
                <!-- Left Brand & Info -->
                <div class="flex items-center gap-3">
                    <div class="w-7 h-7 rounded-lg bg-emerald-500/10 border border-emerald-500/30 flex items-center justify-center text-emerald-500 font-bold text-xs">
                        EWS
                    </div>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">
                        &copy; {{ date('Y') }} <span class="font-semibold text-zinc-800 dark:text-zinc-200">EWSCare</span>. Sistem Peringatan Dini Kesehatan Santri Pondok Pesantren.
                    </p>
                </div>

                <!-- Right System Status & Quick Links -->
                <div class="flex items-center gap-6">
                    <div class="inline-flex items-center gap-2 text-xs text-emerald-600 dark:text-emerald-400 bg-emerald-500/10 px-3 py-1 rounded-full border border-emerald-500/20">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                        Status Sistem: Aktif
                    </div>
                    @auth
                        <a href="{{ route('dashboard') }}" class="text-xs font-medium text-zinc-600 hover:text-emerald-500 dark:text-zinc-400 dark:hover:text-emerald-400">Dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="text-xs font-medium text-zinc-600 hover:text-emerald-500 dark:text-zinc-400 dark:hover:text-emerald-400">Login Akses</a>
                    @endauth
                </div>

            </div>
        </footer>

        @fluxScripts
    </body>
</html>
