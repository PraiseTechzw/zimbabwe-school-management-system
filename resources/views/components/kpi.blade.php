@props(['label' => '', 'value' => '—', 'note' => null, 'demo' => false, 'data' => false])

<div class="fs-card px-4 py-4">
    <div class="flex items-start justify-between gap-3">
        <p class="text-[13px] text-muted">{{ $label }}</p>
        @if($demo)
            <span class="fs-pill fs-pill-warning shrink-0">Demo data</span>
        @elseif($data)
            <span class="h-2 w-2 shrink-0 rounded-full bg-data" aria-label="Current period"></span>
        @endif
    </div>
    <p class="fs-figure mt-2 text-[30px] font-normal leading-none {{ $data ? 'text-data' : 'text-ink' }}">{{ $value }}</p>
    @if($note !== null)
        <p class="mt-2 text-[13px] text-muted">{{ $note }}</p>
    @endif
</div>
