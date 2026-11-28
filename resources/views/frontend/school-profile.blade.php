<x-layouts.app :title="$title ?? 'School profile'">
<x-app-shell title="School profile" :user="$user" :school="$school" :academic-year="$academicYear"
             :breadcrumbs="[['label' => 'School']]">
    <x-card title="Identity" subtitle="Read-only view of the authorised school context.">
        @if($school === null)
            <x-empty-state title="No school context" message="Your account is not linked to a school yet." />
        @else
            <dl class="grid gap-x-8 gap-y-4 sm:grid-cols-3">
                <div>
                    <dt class="text-[13px] text-muted">Name</dt>
                    <dd class="mt-0.5 text-sm font-medium">{{ $school['name'] }}</dd>
                </div>
                <div>
                    <dt class="text-[13px] text-muted">Type</dt>
                    <dd class="mt-0.5 text-sm">{{ $school['type'] }}</dd>
                </div>
                <div>
                    <dt class="text-[13px] text-muted">School ID</dt>
                    <dd class="fs-figure mt-0.5 text-sm">{{ $school['id'] }}</dd>
                </div>
            </dl>
        @endif
    </x-card>

    <x-card title="Identifiers" class="mt-4" subtitle="Codes assigned to this school.">
        @if($identifiers === [])
            <x-empty-state title="No identifiers recorded"
                           message="Identifiers added by the school office will appear here." />
        @else
            <x-table>
                <x-slot:header>
                    <tr>
                        <th scope="col">Type</th>
                        <th scope="col">Value</th>
                        <th scope="col">Verification</th>
                    </tr>
                </x-slot:header>
                @foreach($identifiers as $identifier)
                    <tr>
                        <td>{{ $identifier['type'] }}</td>
                        <td class="fs-figure">{{ $identifier['value'] }}</td>
                        <td><x-status-pill :status="$identifier['verification_status']" /></td>
                    </tr>
                @endforeach
            </x-table>
        @endif
    </x-card>
</x-app-shell>
</x-layouts.app>
