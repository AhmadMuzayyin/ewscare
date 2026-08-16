@props([
'sidebar' => false,
])

<flux:brand name="" {{ $attributes }}>
    <x-slot name="logo" class="flex items-center justify-center">
        <x-app-logo-icon />
    </x-slot>
</flux:brand>