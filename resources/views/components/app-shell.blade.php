@props([
    'title' => '',
    'breadcrumbs' => [],
    'user' => null,
])

@php
    $currentRoute = request()->route()?->getName();
    $hidden = array_fill_keys(array_values((array) config('frontend.hidden_modules', [])), true);
    $groups = [];

    foreach ((array) config('frontend.navigation', []) as $heading => $items) {
        $visible = array_values(array_filter(
            $items,
            static fn (array $item): bool => ! isset($hidden[$item['key'] ?? '']),
        ));

        if ($visible !== []) {
            $groups[$heading] = $visible;
        }
    }
@endphp

<div class="min-h-screen lg:flex" x-data="{ menu: false }">
    <aside class="hidden w-64 shrink-0 flex-col border-r border-border bg-surface lg:flex">
        <a href="{{ route('frontend.dashboard') }}" class="flex h-14 items-center gap-2 border-b border-border px-5 text-[15px] font-semibold">
            <x-icon name="school" class="h-5 w-5" />
            <span>School Manager</span>
        </a>
        <div class="flex-1 overflow-y-auto pt-4">
            <x-nav-list :groups="$groups" :current="$currentRoute" />
        </div>
    </aside>

    <div class="min-w-0 flex-1">
        <header class="sticky top-0 z-20 border-b border-border bg-surface">
            <div class="flex h-14 items-center justify-between gap-4 px-4 sm:px-6">
                <div class="flex min-w-0 items-center gap-3">
                    <button type="button" class="fs-btn fs-btn-secondary fs-btn-compact lg:hidden"
                            aria-label="Open navigation" aria-controls="mobile-navigation" @click="menu = true">
                        <x-icon name="menu" class="h-4 w-4" />
                    </button>
                    <nav aria-label="Breadcrumb" class="min-w-0">
                        <ol class="flex items-center gap-1.5 text-[13px] text-muted">
                            <li><a href="{{ route('frontend.dashboard') }}" class="hover:text-ink">Home</a></li>
                            @foreach($breadcrumbs as $crumb)
                                <li class="flex items-center gap-1.5">
                                    <x-icon name="chevron" class="h-3 w-3 shrink-0" />
                                    @if(! empty($crumb['url']))
                                        <a href="{{ $crumb['url'] }}" class="hover:text-ink">{{ $crumb['label'] }}</a>
                                    @else
                                        <span class="truncate text-ink">{{ $crumb['label'] }}</span>
                                    @endif
                                </li>
                            @endforeach
                        </ol>
                    </nav>
                </div>

                <div class="flex shrink-0 items-center gap-3">
                    @if($user !== null && $user !== [])
                        <span class="hidden text-[13px] text-muted sm:inline">{{ $user['name'] }}</span>
                        <span class="flex h-7 w-7 items-center justify-center rounded-full bg-active text-[13px] font-semibold"
                              aria-hidden="true">{{ mb_substr((string) $user['name'], 0, 1) }}</span>
                        <button type="button" class="text-[13px] text-muted underline-offset-2 hover:text-ink hover:underline"
                                data-api-logout>Log out</button>
                    @endif
                </div>
            </div>
        </header>

        <div class="mx-auto w-full max-w-[1240px] px-4 pt-6 sm:px-6">
            <h1 class="text-2xl font-semibold tracking-tight">{{ $title }}</h1>
            {{ $header ?? '' }}
        </div>

        <main class="mx-auto w-full max-w-[1240px] px-4 py-6 sm:px-6">
            {{ $slot }}
        </main>
    </div>

    <div x-cloak x-show="menu" class="fixed inset-0 z-50 lg:hidden" @keydown.escape.window="menu = false">
        <div class="absolute inset-0 bg-black/30" @click="menu = false" aria-hidden="true"></div>
        <aside id="mobile-navigation" class="absolute inset-y-0 left-0 w-[280px] max-w-[85vw] overflow-y-auto bg-surface shadow-lg"
               role="dialog" aria-modal="true" aria-label="Navigation">
            <div class="flex h-14 items-center justify-between border-b border-border px-5">
                <span class="text-[15px] font-semibold">School Manager</span>
                <button type="button" class="fs-btn fs-btn-secondary fs-btn-compact" aria-label="Close navigation"
                        @click="menu = false">
                    <x-icon name="close" class="h-4 w-4" />
                </button>
            </div>
            <div class="pt-4">
                <x-nav-list :groups="$groups" :current="$currentRoute" />
            </div>
        </aside>
    </div>
</div>
