@blaze(fold: true)

@props([
    'paginate' => null,
])

@php
$classes = Flux::classes()
    ->add('[:where(&)]:min-w-full table-auto border-separate border-spacing-0 isolate')
    ->add('text-zinc-800 dark:text-zinc-200')
    // We want whitespace-nowrap for the table, but not for modals and dropdowns...
    ->add('whitespace-nowrap [&_dialog]:whitespace-normal [&_[popover]]:whitespace-normal')
    ;

$containerClasses = Flux::classes()
    ->add('flex flex-col')
    ->add($attributes->pluck('container:class'))
    ;
@endphp

<div class="{{ $containerClasses }}">
    {{ $header ?? '' }}

    <ui-table-scroll-area class="overflow-auto">
        <table {{ $attributes->class($classes) }} data-flux-table>
            {{ $slot }}
        </table>
    </ui-table-scroll-area>

    {{ $footer ?? '' }}

    <?php if ($paginate): ?>
        <div class="px-6 py-4 border-t border-zinc-200 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-900/30">
            <?php $paginationAttributes = Flux::attributesAfter('pagination:', $attributes, ['paginator' => $paginate, 'class' => 'shrink-0']); ?>
            <flux:pagination :attributes="$paginationAttributes" />
        </div>
    <?php endif; ?>
</div>
