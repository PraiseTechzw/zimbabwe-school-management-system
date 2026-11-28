@props([
    'title' => '',
    'breadcrumbs' => [],
    'user' => null,
    'school' => null,
    'academicYear' => null,
])

@php
    $currentRoute = request()->route()?->getName();
    $hidden = array_fill_keys(array_values((array) config('frontend.hidden_modules', [])), true);
    $actor = auth()->user();
    $permissionSchool = app(\App\Domains\School\Context\SchoolContext::class)->current();
    $permissions = app(\App\Domains\Authorization\Services\SchoolPermissions::class);
    $canSee = static function (array $item) use ($actor, $permissionSchool, $permissions): bool {
        $permission = $item['permission'] ?? null;

        if ($permission === null) {
            return true;
        }

        return $actor instanceof \App\Models\User
            && $permissionSchool !== null
            && $permissions->has($actor, $permissionSchool, $permission);
    };
    $groups = [];

    foreach ((array) config('frontend.navigation', []) as $heading => $items) {
        $visible = array_values(array_filter(
            $items,
            static fn (array $item): bool => ! isset($hidden[$item['key'] ?? '']) && $canSee($item),
        ));

        if ($visible !== []) {
            $groups[$heading] = $visible;
        }
    }
@endphp

<div class="min-h-screen lg:flex" x-data="{
    menu: false,
    menuOpener: null,
    openMenu(event) {
        this.menuOpener = event.currentTarget;
        this.menu = true;
        this.$nextTick(() => requestAnimationFrame(() => this.$refs.mobileNavigation?.querySelector('button')?.focus()));
    },
    closeMenu() {
        this.menu = false;
        this.$nextTick(() => this.menuOpener?.focus());
    },
    trapMenuFocus(event) {
        const focusable = this.$refs.mobileNavigation?.querySelectorAll(`a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex='-1'])`);
        if (!focusable || focusable.length === 0) return;
        const first = focusable[0];
        const last = focusable[focusable.length - 1];
        if (event.shiftKey && document.activeElement === first) {
            event.preventDefault();
            last.focus();
        } else if (!event.shiftKey && document.activeElement === last) {
            event.preventDefault();
            first.focus();
        }
    }
}">
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
                            aria-label="Open navigation" aria-controls="mobile-navigation" @click="openMenu($event)">
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
                        <div class="hidden text-right sm:block">
                            <span class="block text-[13px] font-medium">{{ $user['name'] }}</span>
                            @if(($user['email'] ?? '') !== '')
                                <span class="block text-[12px] text-muted">{{ $user['email'] }}</span>
                            @endif
                        </div>
                        <span class="flex h-8 w-8 items-center justify-center rounded-full bg-active text-[13px] font-semibold"
                              title="{{ $user['name'] }}" aria-hidden="true">{{ mb_substr((string) $user['name'], 0, 1) }}</span>
                        <button type="button" class="fs-btn fs-btn-secondary fs-btn-compact" data-api-logout
                                aria-label="Log out">Log out</button>
                    @endif
                </div>
            </div>
        </header>

        <div class="mx-auto w-full max-w-[1240px] px-4 pt-6 sm:px-6">
            <h1 class="text-2xl font-semibold tracking-tight">{{ $title }}</h1>
            {{ $header ?? '' }}
            <x-context-strip :school="$school" :academic-year="$academicYear" class="mt-4" />
        </div>

        <main class="mx-auto w-full max-w-[1240px] px-4 py-6 sm:px-6">
            {{ $slot }}
        </main>
    </div>

    <div x-cloak x-show="menu" class="fixed inset-0 z-50 lg:hidden" @keydown.escape.window="closeMenu()">
        <div class="absolute inset-0 bg-black/30" @click="closeMenu()" aria-hidden="true"></div>
        <aside id="mobile-navigation" x-ref="mobileNavigation"
               class="absolute inset-y-0 left-0 w-[280px] max-w-[85vw] overflow-y-auto bg-surface shadow-lg"
               role="dialog" aria-modal="true" aria-label="Navigation" @keydown.tab="trapMenuFocus($event)">
            <div class="flex h-14 items-center justify-between border-b border-border px-5">
                <span class="text-[15px] font-semibold">School Manager</span>
                <button type="button" class="fs-btn fs-btn-secondary fs-btn-compact" aria-label="Close navigation"
                        @click="closeMenu()">
                    <x-icon name="close" class="h-4 w-4" />
                </button>
            </div>
            <div class="px-3 pt-3">
                <x-context-strip :school="$school" :academic-year="$academicYear" compact />
            </div>
            <div class="pt-1">
                <x-nav-list :groups="$groups" :current="$currentRoute" />
            </div>
        </aside>
    </div>
</div>
