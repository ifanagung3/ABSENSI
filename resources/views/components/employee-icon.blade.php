@props(['name'])

<svg
    {{ $attributes->merge([
        'class' => 'h-5 w-5',
        'viewBox' => '0 0 24 24',
        'fill' => 'none',
        'stroke' => 'currentColor',
        'stroke-width' => '1.8',
        'stroke-linecap' => 'round',
        'stroke-linejoin' => 'round',
        'aria-hidden' => 'true',
    ]) }}
>
    @switch($name)
        @case('home')
            <path d="m3 11 9-8 9 8" />
            <path d="M5 10v10h14V10" />
            <path d="M9 20v-6h6v6" />
            @break

        @case('history')
            <circle cx="12" cy="12" r="9" />
            <path d="M12 7v5l3 2" />
            @break

        @case('attendance')
            <circle cx="12" cy="12" r="3.5" />
            <path d="M8 4H5a1 1 0 0 0-1 1v3" />
            <path d="M16 4h3a1 1 0 0 1 1 1v3" />
            <path d="M20 16v3a1 1 0 0 1-1 1h-3" />
            <path d="M8 20H5a1 1 0 0 1-1-1v-3" />
            @break

        @case('leave')
            <path d="M6 3h9l3 3v15H6z" />
            <path d="M14 3v4h4" />
            <path d="M9 12h6" />
            <path d="M9 16h6" />
            @break

        @case('user')
            <circle cx="12" cy="8" r="4" />
            <path d="M4.5 21a7.5 7.5 0 0 1 15 0" />
            @break

        @case('logout')
            <path d="M10 17l5-5-5-5" />
            <path d="M15 12H3" />
            <path d="M14 3h5a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-5" />
            @break

        @case('clock')
            <circle cx="12" cy="12" r="9" />
            <path d="M12 7v5h4" />
            @break

        @case('pin')
            <path d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0Z" />
            <circle cx="12" cy="10" r="2.5" />
            @break

        @case('check')
            <path d="m5 12 4 4L19 6" />
            @break

        @case('dashboard')
            <rect x="3" y="3" width="7" height="7" rx="1" />
            <rect x="14" y="3" width="7" height="7" rx="1" />
            <rect x="3" y="14" width="7" height="7" rx="1" />
            <rect x="14" y="14" width="7" height="7" rx="1" />
            @break

        @case('users')
            <circle cx="9" cy="8" r="3.5" />
            <path d="M3 20a6 6 0 0 1 12 0" />
            <path d="M16 5.5a3.5 3.5 0 0 1 0 6.5" />
            <path d="M18 14.5a5 5 0 0 1 3 4.5" />
            @break

        @case('organization')
            <rect x="8" y="3" width="8" height="5" rx="1" />
            <rect x="3" y="16" width="7" height="5" rx="1" />
            <rect x="14" y="16" width="7" height="5" rx="1" />
            <path d="M12 8v4M6.5 16v-4h11v4" />
            @break

        @case('calendar')
            <rect x="3" y="5" width="18" height="16" rx="2" />
            <path d="M7 3v4M17 3v4M3 10h18" />
            @break

        @case('clipboard-check')
            <path d="M9 5H6a2 2 0 0 0-2 2v13h16V7a2 2 0 0 0-2-2h-3" />
            <rect x="9" y="3" width="6" height="4" rx="1" />
            <path d="m8 14 2.5 2.5L16 11" />
            @break

        @case('chart')
            <path d="M4 20V10M10 20V4M16 20v-7M22 20H2" />
            @break

        @case('audit')
            <path d="M9 6h11M9 12h11M9 18h11" />
            <circle cx="4" cy="6" r="1" />
            <circle cx="4" cy="12" r="1" />
            <circle cx="4" cy="18" r="1" />
            @break

        @case('alert')
            <path d="M12 3 2.5 20h19Z" />
            <path d="M12 9v4M12 17h.01" />
            @break

        @case('review')
            <circle cx="11" cy="11" r="7" />
            <path d="m16 16 5 5M11 8v3l2 1" />
            @break

        @case('menu')
            <path d="M4 7h16M4 12h16M4 17h16" />
            @break

        @case('close')
            <path d="m6 6 12 12M18 6 6 18" />
            @break

        @case('chevron-right')
            <path d="m9 18 6-6-6-6" />
            @break
    @endswitch
</svg>
