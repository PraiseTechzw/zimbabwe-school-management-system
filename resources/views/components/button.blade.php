@props(['variant' => 'primary', 'type' => 'button'])

<button type="{{ $type }}" {{ $attributes->merge(['class' => 'fs-btn fs-btn-'.$variant]) }}>
    {{ $slot }}
</button>
