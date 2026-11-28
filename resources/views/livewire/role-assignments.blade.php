<x-app-shell title="Roles" :user="$user" :school="$school" :academic-year="$academicYear"
             :breadcrumbs="[['label' => 'System'], ['label' => 'Roles']]">
    <x-slot:header>
        <p class="mt-1 text-[13px] text-muted">
            School roles are assigned from verified members. The backend remains the authority for who can view or change them.
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

    @if(! $canView)
        <x-card title="Role assignments">
            <x-empty-state title="Not available to this account"
                           message="Your account does not have the backend permission needed to view role assignments." />
        </x-card>
    @else
        @if($canManage)
            <x-card title="Assign a role" subtitle="The verified assignment contract accepts a user ID and a configured role.">
                <form wire:submit="assign" class="grid gap-4 sm:grid-cols-2">
                    <x-input name="role_user_id" label="User ID" type="number" min="1"
                             wire:model.blur="userId"
                             help="Use the numeric ID of a member of this school. Membership is checked by the backend."
                             :error="$errorsByField['user_id'] ?? null" />
                    <x-select name="role_name" label="Role" :options="$roleOptions"
                              placeholder="Select a role" wire:model.blur="role"
                              :error="$errorsByField['role'] ?? null" />
                    <div class="flex flex-wrap items-center gap-2 sm:col-span-2">
                        <x-button type="submit" wire:loading.attr="disabled" wire:target="assign">Assign role</x-button>
                        <span class="text-[13px] text-muted" wire:loading wire:target="assign">Saving…</span>
                    </div>
                </form>
            </x-card>
        @else
            <x-card title="Assign a role">
                <p class="text-[13px] text-muted">
                    Your account can view assignments but cannot add or remove them in this school.
                </p>
            </x-card>
        @endif

        <x-card title="Current assignments" class="mt-4"
                subtitle="Source and assignment date are recorded by the role assignment contract.">
            @if($assignments === [])
                <x-empty-state title="No role assignments"
                               message="Roles assigned in this school will appear here." />
            @else
                <x-table>
                    <x-slot:header>
                        <tr>
                            <th scope="col">User ID</th>
                            <th scope="col">Role</th>
                            <th scope="col">Source</th>
                            <th scope="col">Assigned</th>
                            @if($canManage)
                                <th scope="col"><span class="sr-only">Actions</span></th>
                            @endif
                        </tr>
                    </x-slot:header>
                    @foreach($assignments as $assignment)
                        <tr>
                            <td class="fs-figure">{{ $assignment['user_id'] }}</td>
                            <td>{{ $roleLabels[(string) $assignment['role']] ?? $assignment['role'] }}</td>
                            <td>{{ $assignment['source'] }}</td>
                            <td class="fs-figure whitespace-nowrap">{{ $assignment['created_at'] }}</td>
                            @if($canManage)
                                <td class="text-right">
                                    <x-button variant="danger" class="fs-btn-compact"
                                              wire:confirm="Remove this role assignment?"
                                              wire:click="remove({{ $assignment['id'] }})"
                                              wire:loading.attr="disabled" wire:target="remove({{ $assignment['id'] }})"
                                              aria-label="Remove {{ $roleLabels[(string) $assignment['role']] ?? $assignment['role'] }} from user {{ $assignment['user_id'] }}">
                                        Remove
                                    </x-button>
                                </td>
                            @endif
                        </tr>
                    @endforeach
                </x-table>
            @endif
        </x-card>
    @endif
</x-app-shell>
