@props(['name' => 'dashboard', 'class' => 'h-4 w-4'])

<svg xmlns="http://www.w3.org/2000/svg" class="{{ $class }}" viewBox="0 0 24 24" fill="none"
     stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    @switch($name)
        @case('dashboard')
            <rect x="3" y="3" width="7" height="7" rx="1" />
            <rect x="14" y="3" width="7" height="7" rx="1" />
            <rect x="3" y="14" width="7" height="7" rx="1" />
            <rect x="14" y="14" width="7" height="7" rx="1" />
            @break
        @case('school')
            <path d="M3 10 12 4l9 6" />
            <path d="M5 10v9h14v-9" />
            <path d="M10 19v-5h4v5" />
            @break
        @case('calendar')
            <rect x="3" y="5" width="18" height="16" rx="2" />
            <path d="M3 10h18M8 3v4M16 3v4" />
            @break
        @case('users')
            <circle cx="9" cy="8" r="3" />
            <path d="M3 20c0-3.3 2.7-6 6-6s6 2.7 6 6" />
            <path d="M16 5.2a3 3 0 0 1 0 5.6M18 14c1.8.9 3 2.6 3 5" />
            @break
        @case('book')
            <path d="M4 5.5A2.5 2.5 0 0 1 6.5 3H20v16H6.5A2.5 2.5 0 0 0 4 21.5z" />
            <path d="M4 5.5v16" />
            @break
        @case('clipboard')
            <rect x="5" y="4" width="14" height="17" rx="2" />
            <path d="M9 4V3h6v1M9 10h6M9 14h6" />
            @break
        @case('wallet')
            <rect x="3" y="6" width="18" height="14" rx="2" />
            <path d="M3 10h18M16 15h2" />
            @break
        @case('cog')
            <circle cx="12" cy="12" r="3" />
            <path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4" />
            @break
        @case('route')
            <circle cx="6" cy="18" r="2" />
            <circle cx="18" cy="6" r="2" />
            <path d="M8 18h5a3 3 0 0 0 0-6h-2a3 3 0 0 1 0-6h5" />
            @break
        @case('menu')
            <path d="M4 7h16M4 12h16M4 17h16" />
            @break
        @case('close')
            <path d="M6 6l12 12M18 6 6 18" />
            @break
        @case('chevron')
            <path d="m9 6 6 6-6 6" />
            @break
        @default
            <circle cx="12" cy="12" r="8" />
    @endswitch
</svg>
