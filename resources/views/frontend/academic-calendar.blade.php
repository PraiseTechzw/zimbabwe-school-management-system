<x-app-shell title="Academic calendar" :user="$user"
             :breadcrumbs="[['label' => 'School', 'url' => route('frontend.school-profile')], ['label' => 'Academic calendar']]">
    <x-card title="Years" subtitle="Dates and terms come from the school's calendar records.">
        <x-slot:action>
            <a href="{{ route('frontend.calendar-manage') }}" class="fs-btn fs-btn-secondary fs-btn-compact">Manage calendar</a>
        </x-slot:action>
        @if($years === [])
            <x-empty-state title="No academic years"
                           message="An academic year has not been added to this school yet." />
        @else
            <div class="mb-4 flex flex-wrap items-center gap-2">
                @foreach($years as $year)
                    <a href="{{ route('frontend.academic-calendar', ['year' => $year['id']]) }}"
                       @class(['fs-btn', $selectedYear !== null && $selectedYear['id'] === $year['id'] ? 'fs-btn-primary' : 'fs-btn-secondary'])>
                        {{ $year['name'] }}
                    </a>
                @endforeach
            </div>

            @if($selectedYear !== null)
                <dl class="grid gap-x-8 gap-y-4 sm:grid-cols-4">
                    <div>
                        <dt class="text-[13px] text-muted">Name</dt>
                        <dd class="mt-0.5 text-sm font-medium">{{ $selectedYear['name'] }}</dd>
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
                        <dt class="text-[13px] text-muted">Status</dt>
                        <dd class="mt-0.5"><x-status-pill :status="$selectedYear['status']" /></dd>
                    </div>
                </dl>
            @endif
        @endif
    </x-card>

    <x-card title="Periods" class="mt-4">
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
