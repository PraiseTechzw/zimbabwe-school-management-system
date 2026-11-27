<x-layouts.app :title="$title ?? $label">
<x-app-shell title="{{ $label }}" :user="$user" :school="$school" :academic-year="$academicYear"
             :breadcrumbs="[['label' => 'Planned module']]">
    <x-card>
        <x-empty-state title="This module is not built yet"
                       message="The navigation shows planned areas so the full system is visible, but no screens or records exist for {{ $label }} yet.">
            <a href="{{ route('frontend.dashboard') }}" class="fs-btn fs-btn-secondary">Back to dashboard</a>
        </x-empty-state>
    </x-card>

    <p class="mt-4 text-[13px] text-muted">
        Module key: <code class="rounded bg-active px-1 py-0.5">{{ $module }}</code>.
        No data is shown here because no API contract has been approved for it.
    </p>
</x-app-shell>
</x-layouts.app>
