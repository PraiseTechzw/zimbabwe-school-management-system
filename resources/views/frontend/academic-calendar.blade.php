<x-layouts.app :title="$title ?? 'Academic calendar'">
<x-app-shell title="Academic calendar" :user="$user" :school="$school" :academic-year="$academicYear"
             :breadcrumbs="[['label' => 'School', 'url' => route('frontend.school-profile')], ['label' => 'Academic calendar']]">
    <x-slot:header>
        <p class="mt-1 text-[13px] text-muted">Dates and terms come from the school's own calendar records. Nothing is inferred.</p>
    </x-slot:header>

    <x-card title="Year overview" subtitle="The selected year and the periods recorded in it.">
        @if($canManageCalendar)
            <x-slot:action>
                <a href="{{ route('frontend.calendar-manage') }}" class="fs-btn fs-btn-secondary fs-btn-compact">Manage calendar</a>
            </x-slot:action>
        @endif

        @if($selectedYear === null)
            <x-empty-state title="No academic years"
                           message="An academic year has not been added to this school yet." />
        @else
            <dl class="grid gap-x-8 gap-y-4 sm:grid-cols-4">
                <div>
                    <dt class="text-[13px] text-muted">Selected year</dt>
                    <dd class="fs-figure mt-0.5 text-sm font-medium">{{ $selectedYear['name'] }}</dd>
                </div>
                <div>
                    <dt class="text-[13px] text-muted">Starts</dt>
                    <dd class="fs-figure mt-0.5 text-sm">{{ $selectedYear['start_date'] }}</dd>
                </div>
                <div>
                    <dt class="text-[13px] text-muted">Ends</dt>
                    <dd class="fs-figure mt-0.5 text-sm">{{ $selectedYear['end_date'] }}</dd>
                </div>
                <div>
                    <dt class="text-[13px] text-muted">Periods</dt>
                    <dd class="fs-figure mt-0.5 text-sm text-data">{{ count($periods) }}</dd>
                </div>
            </dl>

            <div class="mt-4 border-t border-rule pt-4">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="text-[13px] text-muted">Years</span>
                    @foreach($years as $year)
                        <a href="{{ route('frontend.academic-calendar', ['year' => $year['id']]) }}"
                           @class(['fs-btn fs-btn-compact',
                                   'fs-btn-primary' => $selectedYear['id'] === $year['id'],
                                   'fs-btn-secondary' => $selectedYear['id'] !== $year['id']])>
                            {{ $year['name'] }}
                        </a>
                    @endforeach
                </div>
                <p class="mt-3 text-[13px] text-muted">
                    Source: {{ ($selectedYear['source'] ?? '') !== '' ? $selectedYear['source'] : 'Not recorded' }}
                    <span class="fs-figure"> · Status: {{ $selectedYear['status'] }}</span>
                </p>
            </div>
        @endif
    </x-card>

    <x-card title="Calendar check" class="mt-4"
            subtitle="Integrity rules run against the selected year only.">
        @if($validation === null)
            <x-empty-state title="No year to check" message="Add an academic year before running a check." />
        @elseif($validation['valid'])
            <p class="text-sm font-medium text-positive">No issues found for {{ $selectedYear['name'] }}.</p>
        @else
            <ul class="list-disc space-y-1 pl-5 text-[13px] text-negative">
                @foreach($validation['issues'] as $issue)
                    <li>{{ $issue }}</li>
                @endforeach
            </ul>
        @endif
    </x-card>

    <x-card title="Periods" class="mt-4"
            subtitle="Terms, breaks and other dated divisions recorded for the selected year.">
        @if($periods === [])
            <x-empty-state title="No periods" message="Terms and holidays for this year have not been added yet." />
        @else
            <x-table>
                <x-slot:header>
                    <tr>
                        <th scope="col">#</th>
                        <th scope="col">Name</th>
                        <th scope="col">Type</th>
                        <th scope="col">Dates</th>
                        <th scope="col">Status</th>
                    </tr>
                </x-slot:header>
                @foreach($periods as $period)
                    <tr>
                        <td class="fs-figure text-muted">{{ $period['sequence'] }}</td>
                        <td>{{ $period['name'] }}</td>
                        <td>{{ $period['type'] }}</td>
                        <td class="fs-figure whitespace-nowrap">{{ $period['start_date'] }} – {{ $period['end_date'] }}</td>
                        <td><x-status-pill :status="$period['status']" /></td>
                    </tr>
                @endforeach
            </x-table>
        @endif
    </x-card>
</x-app-shell>
</x-layouts.app>
