@props(['groups' => [], 'current' => null])

<nav aria-label="Modules" class="px-3 pb-8">
    @foreach($groups as $heading => $items)
        <p class="fs-nav-group">{{ $heading }}</p>
        <ul class="mt-1">
            @foreach($items as $item)
                @php
                    $available = (bool) ($item['available'] ?? false);
                    $href = $available && isset($item['route'])
                        ? route((string) $item['route'])
                        : route('frontend.module', (string) $item['key']);
                @endphp
                <li>
                    <x-nav-link :label="$item['label']" :href="$href" :icon="$item['icon'] ?? null"
                                :active="$current === ($item['route'] ?? null) && $available"
                                :planned="! $available" />
                </li>
            @endforeach
        </ul>
    @endforeach
</nav>
