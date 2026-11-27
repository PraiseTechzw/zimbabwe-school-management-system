@props(['lines' => 3])

<div class="fs-card space-y-3 p-4" aria-hidden="true" role="presentation">
    @for($i = 0; $i < $lines; $i++)
        <div class="fs-skeleton-bar" style="width: {{ $i === $lines - 1 ? '58%' : '100%' }}"></div>
    @endfor
</div>
