<x-app-shell title="Dashboard" :user="$user" :school="$school" :academic-year="$currentYear">
    <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
        <x-kpi label="Current academic year" :value="$currentYear['name'] ?? '—'" :data="$currentYear !== null"
               :note="$currentYear === null
                    ? 'No published year in this school context'
                    : ($currentYear['start_date'].' to '.$currentYear['end_date'])" />

        <x-kpi label="School identifiers" :value="(string) $identifierCount"
               :note="$identifierCount === 0 ? 'No identifiers recorded' : $verifiedCount.' verified'" />

        <x-kpi label="Screens ready" :value="(string) count($readyModules)"
               :note="'Available destinations in this school context'" />

        @if($attention !== [])
            @php($demo = (bool) ($attention[0]['is_demo'] ?? false))
            <x-kpi label="Needs attention" :value="$demo ? '—' : (string) count($attention)"
                   :demo="$demo" :note="$demo ? 'Counts shown for demonstration only' : 'Across your modules'" />
        @else
            <x-kpi label="Needs attention" value="—" note="No aggregate endpoint available yet" />
        @endif
    </div>

    <x-card title="School at a glance" class="mt-4"
            subtitle="A dash means the module has no verified live endpoint in this branch. No placeholder counts are shown.">
        <ul class="grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
            @foreach($gaps as $gap)
                <li class="fs-panel px-3 py-3">
                    <p class="text-[13px] text-muted">{{ $gap['label'] }}</p>
                    <p class="fs-figure mt-2 text-[24px] leading-none {{ $gap['label'] === 'Calendar events' && $currentYear !== null ? 'text-data' : 'text-ink' }}">
                        {{ $gap['value'] }}
                    </p>
                    <p class="mt-2 text-[13px] text-muted">{{ $gap['note'] }}</p>
                </li>
            @endforeach
        </ul>
    </x-card>

    <div class="mt-4 grid gap-4">
        <x-card title="School record">
            @if($school === null)
                <x-empty-state title="No school context" message="Your account is not linked to a school yet." />
            @else
                <dl class="grid gap-x-8 gap-y-4 sm:grid-cols-2">
                    <div>
                        <dt class="text-[13px] text-muted">Name</dt>
                        <dd class="mt-0.5 text-sm font-medium">{{ $school['name'] }}</dd>
                    </div>
                    <div>
                        <dt class="text-[13px] text-muted">Type</dt>
                        <dd class="mt-0.5 text-sm">{{ $school['type'] }}</dd>
                    </div>
                    <div>
                        <dt class="text-[13px] text-muted">Signed in as</dt>
                        <dd class="mt-0.5 text-sm">{{ $user['name'] ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-[13px] text-muted">Account status</dt>
                        <dd class="mt-0.5"><x-status-pill :status="$user['status'] ?? ''" /></dd>
                    </div>
                </dl>
                <div class="mt-5 border-t border-rule pt-4">
                    <a href="{{ route('frontend.school-profile') }}" class="text-[13px] font-medium underline-offset-2 hover:underline">
                        View school profile
                    </a>
                </div>
            @endif
        </x-card>

        <x-card title="Needs attention">
            @if($attention === [])
                <x-empty-state title="No live figures"
                               message="Cross-module counts are not available until their endpoints are built." />
            @else
                <ul class="divide-y divide-rule">
                    @foreach($attention as $item)
                        <li class="flex items-center justify-between gap-3 py-2.5 first:pt-0 last:pb-0">
                            <span class="text-[13px] text-muted">{{ $item['label'] }}</span>
                            <span class="fs-figure text-sm {{ $item['is_demo'] ? 'text-muted' : 'text-ink' }}">{{ $item['value'] }}</span>
                        </li>
                    @endforeach
                </ul>
                <p class="mt-3 text-[13px] text-warning">Demo data — not school records.</p>
            @endif
        </x-card>
    </div>

    <div class="mt-4 grid gap-4 lg:grid-cols-2">
        <x-card title="Available now" subtitle="Quick actions that are ready in this school context.">
            @if($readyModules === [])
                <x-empty-state title="No screens enabled" />
            @else
                <ul class="grid gap-2 sm:grid-cols-2">
                    @foreach($readyModules as $module)
                        <li>
                            <a href="{{ $module['route'] !== null ? route((string) $module['route']) : route('frontend.module', $module['key']) }}"
                               class="fs-panel flex items-center justify-between px-3 py-2.5 text-[13px] hover:bg-active">
                                <span>{{ $module['label'] }}</span>
                                <x-icon name="chevron" class="h-3.5 w-3.5 text-muted" />
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-card>

        <x-card title="Recent activity">
            <x-empty-state title="No activity feed yet"
                           message="There is no verified activity endpoint in this branch, so nothing is shown here." />
        </x-card>
    </div>

    <x-card title="Notices" class="mt-4"
            subtitle="School notices will appear here when a verified notice endpoint is available.">
        <x-empty-state title="No notices endpoint" message="Notices are not invented or read from another module." />
    </x-card>
</x-app-shell>
