@props(['title' => null])

<section {{ $attributes->merge(['class' => 'fs-panel p-4']) }}>
    @if($title !== null)
        <h2 class="mb-3 text-sm font-medium">{{ $title }}</h2>
    @endif
    {{ $slot }}
</section>
