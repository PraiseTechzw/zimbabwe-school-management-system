@props(['status' => ''])

@php
    $normalized = strtolower(str_replace(['_', '-'], ' ', (string) $status));
    $tone = match (true) {
        in_array($normalized, ['active', 'published', 'verified', 'completed', 'valid'], true) => 'positive',
        in_array($normalized, ['draft', 'pending', 'scheduled', 'in review'], true) => 'warning',
        in_array($normalized, ['archived', 'closed', 'inactive', 'invalid'], true) => 'negative',
        default => 'neutral',
    };
@endphp

<span @class(['fs-pill', 'fs-pill-positive' => $tone === 'positive', 'fs-pill-warning' => $tone === 'warning', 'fs-pill-negative' => $tone === 'negative'])>
    {{ $normalized === '' ? '—' : $normalized }}
</span>
