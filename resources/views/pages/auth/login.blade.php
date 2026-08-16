<x-layouts::auth :title="__('Masuk - EWSCare')">
    <div class="flex flex-col gap-6">
        <div class="text-center space-y-1">
            <h2 class="text-xl font-bold text-zinc-900 dark:text-white tracking-tight">Selamat Datang Kembali</h2>
            <p class="text-xs text-zinc-500 dark:text-zinc-400">Masukkan email dan password akun anda untuk masuk</p>
        </div>

        <!-- Session Status -->
        <x-auth-session-status class="text-center text-xs text-emerald-600 dark:text-emerald-400 bg-emerald-500/10 p-3 rounded-lg border border-emerald-500/20" :status="session('status')" />

        <form method="POST" action="{{ route('login.store') }}" class="flex flex-col gap-5">
            @csrf

            <!-- Email Address -->
            <flux:field>
                <flux:label class="text-xs font-semibold text-zinc-700 dark:text-zinc-300">{{ __('Alamat Email') }}</flux:label>
                <flux:input
                    name="email"
                    :value="old('email')"
                    type="email"
                    required
                    autofocus
                    autocomplete="email"
                    placeholder="nama@pesantren.com"
                />
                <flux:error name="email" />
            </flux:field>

            <!-- Password -->
            <flux:field>
                <div class="flex items-center justify-between">
                    <flux:label class="text-xs font-semibold text-zinc-700 dark:text-zinc-300">{{ __('Password') }}</flux:label>
                    @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}" class="text-xs text-emerald-600 hover:text-emerald-500 dark:text-emerald-400 font-medium">
                            Lupa password?
                        </a>
                    @endif
                </div>
                <flux:input
                    name="password"
                    type="password"
                    required
                    autocomplete="current-password"
                    placeholder="••••••••"
                    viewable
                />
                <flux:error name="password" />
            </flux:field>

            <!-- Remember Me -->
            <div class="flex items-center justify-between pt-1">
                <flux:checkbox name="remember" :label="__('Ingat saya di perangkat ini')" :checked="old('remember')" />
            </div>

            <!-- Submit Button -->
            <div class="pt-2">
                <button type="submit" class="w-full inline-flex items-center justify-center px-5 py-3 text-sm font-semibold rounded-xl text-white bg-emerald-600 hover:bg-emerald-500 active:bg-emerald-700 transition-all shadow-md shadow-emerald-500/20 hover:shadow-emerald-500/30 cursor-pointer" data-test="login-button">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                    </svg>
                    {{ __('Masuk ke Sistem') }}
                </button>
            </div>
        </form>
    </div>
</x-layouts::auth>
