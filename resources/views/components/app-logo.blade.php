@props([
    'sidebar' => false,
])

@if($sidebar)
    <flux:sidebar.brand name="EWS CARE" {{ $attributes }}>
        <x-slot name="logo" class="flex items-center justify-center">
            <x-app-logo-icon />
        </x-slot>
    </flux:sidebar.brand>
@else
    <flux:brand name="EWS CARE" {{ $attributes }}>
        <x-slot name="logo" class="flex items-center justify-center">
            <x-app-logo-icon />
        </x-slot>
    </flux:brand>
@endif
