@props([
    'title',
    'description',
])

<div class="flex w-full flex-col text-center">
    <flux:heading>{{ $title }}</flux:heading>
    <flux:subheading>{{ $description }}</flux:subheading>
</div>
