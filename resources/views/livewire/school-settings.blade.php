<x-app-shell title="School settings" :user="$user" :school="$school" :academic-year="$academicYear"
             :breadcrumbs="[['label' => 'School', 'url' => route('frontend.school-profile')], ['label' => 'School settings']]">
    <x-slot:header>
        <p class="mt-1 text-[13px] text-muted">
            Regional preferences for this school. Only fields exposed by the school settings contract can be changed.
        </p>
    </x-slot:header>

    @if($successMessage !== '')
        <div class="fs-panel mb-4 border-positive/30 px-4 py-3" role="status">
            <p class="text-sm font-medium text-positive">{{ $successMessage }}</p>
        </div>
    @endif

    @if($formError !== '')
        <x-error-block :message="$formError" class="mb-4" />
    @endif

    <x-card title="Regional preferences"
            subtitle="A source is required for every save so the change can be traced.">
        @if(! ($settings['persisted'] ?? false))
            <p class="mb-4 fs-panel px-3 py-2.5 text-[13px] text-muted">
                No saved preferences yet. These are the configured defaults; add a source and save to record them.
            </p>
        @endif

        @if($canManage)
            <form wire:submit="save" class="grid gap-4 sm:grid-cols-2">
                <x-select name="settings_locale" label="Locale" :options="$localeOptions"
                          wire:model.blur="locale" :error="$errorsByField['locale'] ?? null" />
                <x-select name="settings_timezone" label="Timezone" :options="$timezoneOptions"
                          wire:model.blur="timezone" :error="$errorsByField['timezone'] ?? null" />
                <div class="sm:col-span-2">
                    <x-input name="settings_source" label="Source" wire:model.blur="source" required
                             help="For example, a school circular or an authorised instruction."
                             :error="$errorsByField['source'] ?? null" />
                </div>
                <x-input name="settings_effective_from" label="Effective from" type="date"
                         wire:model.blur="effectiveFrom"
                         help="Optional. Use the date the preference takes effect."
                         :error="$errorsByField['effective_from'] ?? null" />

                <div class="flex flex-wrap items-center gap-2 sm:col-span-2">
                    <x-button type="submit" wire:loading.attr="disabled" wire:target="save">Save settings</x-button>
                    <span class="text-[13px] text-muted" wire:loading wire:target="save">Saving…</span>
                </div>
            </form>
        @else
            <p class="text-[13px] text-muted">Your account can view these preferences but cannot change them.</p>
        @endif
    </x-card>

    <x-card title="Revision history" class="mt-4"
            subtitle="Earlier saved values are kept as provenance. They are not edited here.">
        @if($revisions === [])
            <x-empty-state title="No revisions yet"
                           message="A revision is recorded when saved preferences are changed again." />
        @else
            <x-table>
                <x-slot:header>
                    <tr>
                        <th scope="col">Version</th>
                        <th scope="col">Values</th>
                        <th scope="col">Source</th>
                        <th scope="col">Effective from</th>
                    </tr>
                </x-slot:header>
                @foreach($revisions as $revision)
                    @php($values = is_array($revision['values'] ?? null) ? $revision['values'] : [])
                    <tr>
                        <td class="fs-figure">{{ $revision['version'] }}</td>
                        <td>
                            <span>{{ $values['locale'] ?? '—' }}</span>
                            <span class="text-muted"> · </span>
                            <span>{{ $values['timezone'] ?? '—' }}</span>
                        </td>
                        <td>{{ $revision['source'] !== '' ? $revision['source'] : '—' }}</td>
                        <td class="fs-figure whitespace-nowrap">{{ $revision['effective_from'] !== '' ? $revision['effective_from'] : '—' }}</td>
                    </tr>
                @endforeach
            </x-table>
        @endif
    </x-card>
</x-app-shell>
