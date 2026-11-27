@props([
    'label' => '',
    'href' => '#',
    'icon' => null,
    'active' => false,
    'planned' => false,
])

<a href="{{ $href }}" @class(['fs-nav-link', 'justify-between' => $planned])
   @if($active) aria-current="page" @endif>
    <span class="flex min-w-0 items-center gap-2.5">
        @if($icon !== null)
            <x-icon :name="$icon" class="h-4 w-4 shrink-0" />
        @endif
        <span class="truncate">{{ $label }}</span>
    </span>
    @if($planned)
        <span class="shrink-0 text-[13px] text-muted">Soon</span>
    @endif
</a>
