@props(['title' => null, 'subtitle' => null])

<section {{ $attributes->merge(['class' => 'fs-card p-4 sm:p-5']) }}>
    @if($title !== null)
        <div class="mb-4 flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1">
            <div>
                <h2 class="text-base font-medium">{{ $title }}</h2>
                @if($subtitle !== null)
                    <p class="mt-1 text-[13px] text-muted">{{ $subtitle }}</p>
                @endif
            </div>
            {{ $action ?? '' }}
        </div>
    @endif
    {{ $slot }}
</section>
