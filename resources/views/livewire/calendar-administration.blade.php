<x-app-shell title="Academic calendar administration" :user="$user"
             :breadcrumbs="[
                 ['label' => 'School', 'url' => route('frontend.school-profile')],
                 ['label' => 'Academic calendar', 'url' => route('frontend.academic-calendar')],
                 ['label' => 'Manage'],
             ]">
    <x-slot:header>
        <p class="mt-1 text-[13px] text-muted">Record dates and source details from the school's own calendar. Nothing is inferred.</p>
    </x-slot:header>

    @if($successMessage !== '')
        <div class="fs-panel mb-4 border-positive/30 px-4 py-3" role="status">
            <p class="text-sm font-medium text-positive">{{ $successMessage }}</p>
        </div>
    @endif

    @if($formError !== '')
        <x-error-block :message="$formError" class="mb-4" />
    @endif

    <div class="grid gap-4">
        <x-card title="Academic years" subtitle="Choose a year to manage its periods.">
            @if($years === [])
                <x-empty-state title="No academic years"
                               message="Add the first year for this school using the form beside this panel." />
            @else
                <div class="flex flex-col gap-2">
                    @foreach($years as $year)
                        <div class="flex items-center gap-2">
                            <button type="button" wire:click="selectYear({{ $year['id'] }})"
                                    @class(['fs-btn flex-1 justify-between text-left',
                                            'fs-btn-primary' => $selectedYear !== null && $selectedYear['id'] === $year['id'],
                                            'fs-btn-secondary' => $selectedYear === null || $selectedYear['id'] !== $year['id']])>
                                <span>{{ $year['name'] }}</span>
                                <span class="fs-figure">{{ $year['start_date'] }} – {{ $year['end_date'] }}</span>
                            </button>
                            <x-button variant="secondary" class="fs-btn-compact"
                                      wire:click="editYear({{ $year['id'] }})"
                                      aria-label="Edit {{ $year['name'] }}">Edit</x-button>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-card>

        <x-card :title="$editingYearId === null ? 'New academic year' : 'Edit academic year'"
                subtitle="Source details are required so every date can be traced.">
            <form wire:submit="saveYear" class="grid gap-4 sm:grid-cols-2">
                <x-input name="year_name" label="Name" wire:model.blur="yearName" required
                         :error="$yearErrors['name'] ?? null" />
                <x-select name="year_status" label="Status" :options="$yearStatusOptions"
                          wire:model.blur="yearStatus" :error="$yearErrors['status'] ?? null" />
                <x-input name="year_start_date" label="Start date" type="date" wire:model.blur="yearStartDate" required
                         :error="$yearErrors['start_date'] ?? null" />
                <x-input name="year_end_date" label="End date" type="date" wire:model.blur="yearEndDate" required
                         :error="$yearErrors['end_date'] ?? null" />
                <x-input name="year_source" label="Source" wire:model.blur="yearSource" required
                         help="For example, a school circular or published calendar."
                         :error="$yearErrors['source'] ?? null" />
                <x-input name="year_source_reference" label="Source reference" wire:model.blur="yearSourceReference"
                         :error="$yearErrors['source_reference'] ?? null" />
                <x-input name="year_source_published_at" label="Source published date" type="date"
                         wire:model.blur="yearSourcePublishedAt"
                         :error="$yearErrors['source_published_at'] ?? null" />

                <div class="flex flex-wrap items-center gap-2 sm:col-span-2">
                    <x-button type="submit" wire:loading.attr="disabled" wire:target="saveYear">Save academic year</x-button>
                    @if($editingYearId !== null)
                        <x-button variant="secondary" wire:click="newYear">Cancel</x-button>
                    @endif
                    <span class="text-[13px] text-muted" wire:loading wire:target="saveYear">Saving…</span>
                </div>
            </form>
        </x-card>
    </div>

    <x-card title="Calendar check"
            subtitle="Runs the configured integrity rules for the selected year."
            class="mt-4">
        @if($selectedYear === null)
            <x-empty-state title="No year selected" message="Add or select an academic year before running a check." />
        @elseif($validation === null)
            <p class="text-[13px] text-muted">No check has been run for {{ $selectedYear['name'] }} yet.</p>
        @elseif($validation['valid'])
            <p class="text-sm font-medium text-positive">No issues found for {{ $selectedYear['name'] }}.</p>
        @else
            <ul class="list-disc space-y-1 pl-5 text-[13px] text-negative">
                @foreach($validation['issues'] as $issue)
                    <li>{{ $issue }}</li>
                @endforeach
            </ul>
        @endif

        @if($selectedYear !== null)
            <div class="mt-4">
                <x-button variant="secondary" wire:click="validateSelectedYear"
                          wire:loading.attr="disabled" wire:target="validateSelectedYear">Run check</x-button>
            </div>
        @endif
    </x-card>

    <x-card title="Periods"
            :subtitle="$selectedYear === null
                ? 'Select an academic year first.'
                : 'Terms, breaks and other dated divisions recorded for '.$selectedYear['name'].'.'"
            class="mt-4">
        @if($selectedYear === null)
            <x-empty-state title="No academic year selected" message="Choose a year above to see its periods." />
        @elseif($periods === [])
            <x-empty-state title="No periods"
                           message="This year has no periods yet. Add the first one using the form below." />
        @else
            <x-table>
                <x-slot:header>
                    <tr>
                        <th scope="col">#</th>
                        <th scope="col">Name</th>
                        <th scope="col">Type</th>
                        <th scope="col">Dates</th>
                        <th scope="col">Status</th>
                        <th scope="col"><span class="sr-only">Actions</span></th>
                    </tr>
                </x-slot:header>
                @foreach($periods as $period)
                    <tr>
                        <td class="fs-figure text-muted">{{ $period['sequence'] }}</td>
                        <td>{{ $period['name'] }}</td>
                        <td>{{ ucfirst((string) $period['type']) }}</td>
                        <td class="fs-figure whitespace-nowrap">{{ $period['start_date'] }} – {{ $period['end_date'] }}</td>
                        <td><x-status-pill :status="$period['status']" /></td>
                        <td class="text-right">
                            <x-button variant="secondary" class="fs-btn-compact"
                                      wire:click="editPeriod({{ $period['id'] }})"
                                      aria-label="Edit {{ $period['name'] }}">Edit</x-button>
                        </td>
                    </tr>
                @endforeach
            </x-table>
        @endif
    </x-card>

    <x-card :title="$editingPeriodId === null ? 'New period' : 'Edit period'"
            subtitle="Periods are checked against the selected academic year."
            class="mt-4">
        @if($selectedYear === null)
            <x-empty-state title="No academic year selected" message="Choose a year above before adding a period." />
        @else
            <form wire:submit="savePeriod" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <x-input name="period_name" label="Name" wire:model.blur="periodName" required
                         :error="$periodErrors['name'] ?? null" />
                <x-select name="period_type" label="Type" :options="$periodTypeOptions"
                          wire:model.blur="periodType" :error="$periodErrors['type'] ?? null" />
                <x-input name="period_sequence" label="Sequence" type="number" min="1" max="10000"
                         wire:model.blur="periodSequence" required
                         :error="$periodErrors['sequence'] ?? null" />
                <x-input name="period_start_date" label="Start date" type="date" wire:model.blur="periodStartDate" required
                         :error="$periodErrors['start_date'] ?? null" />
                <x-input name="period_end_date" label="End date" type="date" wire:model.blur="periodEndDate" required
                         :error="$periodErrors['end_date'] ?? null" />
                <x-select name="period_status" label="Status" :options="$periodStatusOptions"
                          wire:model.blur="periodStatus" :error="$periodErrors['status'] ?? null" />
                <x-select name="period_counting_basis" label="Counting basis" :options="$countingBasisOptions"
                          placeholder="Not specified" wire:model.blur="periodCountingBasis"
                          :error="$periodErrors['counting_basis'] ?? null" />
                <x-input name="period_source" label="Source" wire:model.blur="periodSource" required
                         help="Record where these dates come from."
                         :error="$periodErrors['source'] ?? null" />
                <x-input name="period_source_reference" label="Source reference" wire:model.blur="periodSourceReference"
                         :error="$periodErrors['source_reference'] ?? null" />
                <x-input name="period_source_published_at" label="Source published date" type="date"
                         wire:model.blur="periodSourcePublishedAt"
                         :error="$periodErrors['source_published_at'] ?? null" />

                <div class="flex flex-wrap items-center gap-2 sm:col-span-2 lg:col-span-3">
                    <x-button type="submit" wire:loading.attr="disabled" wire:target="savePeriod">Save period</x-button>
                    @if($editingPeriodId !== null)
                        <x-button variant="secondary" wire:click="newPeriod">Cancel</x-button>
                    @endif
                    <span class="text-[13px] text-muted" wire:loading wire:target="savePeriod">Saving…</span>
                </div>
            </form>
        @endif
    </x-card>
</x-app-shell>
