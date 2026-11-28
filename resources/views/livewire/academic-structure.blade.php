<x-app-shell title="Academic structure" :user="$user"
             :breadcrumbs="[['label' => 'School', 'url' => route('frontend.school-profile')], ['label' => 'Academic structure']]">
    <x-slot:header>
        <p class="mt-1 text-[13px] text-muted">
            Levels, grade levels, classes, streams and offerings recorded for
            {{ $school['name'] ?? 'the current school' }}. Only verified records are shown.
        </p>
        <p class="mt-1 text-[13px] text-muted">
            Subjects and other planned modules are not built yet.
        </p>
    </x-slot:header>

    @if(! $canManage)
        <div class="fs-panel mb-4 border-border px-4 py-3" role="status">
            <p class="text-sm font-medium">Read-only access</p>
            <p class="mt-1 text-[13px] text-muted">You can review this structure, but changing it requires the manage permission.</p>
        </div>
    @endif

    @if($successMessage !== '')
        <div class="fs-panel mb-4 border-positive/30 px-4 py-3" role="status">
            <p class="text-sm font-medium text-positive">{{ $successMessage }}</p>
        </div>
    @endif

    @if($formError !== '')
        <x-error-block :message="$formError" class="mb-4" />
    @endif

    <div class="fs-panel mb-4 flex flex-wrap gap-2 p-2" role="tablist" aria-label="Academic structure sections">
        @foreach($sections as $tab)
            <button type="button" role="tab"
                    aria-selected="{{ $tab['value'] === $section ? 'true' : 'false' }}"
                    wire:click="selectSection('{{ $tab['value'] }}')"
                    @class(['fs-btn fs-btn-compact',
                            'fs-btn-primary' => $tab['value'] === $section,
                            'fs-btn-secondary' => $tab['value'] !== $section])>
                <span>{{ $tab['label'] }}</span>
                <span class="fs-figure">{{ $counts[$tab['value']] ?? 0 }}</span>
            </button>
        @endforeach
    </div>

    <x-card title="Find a record" subtitle="Search and status filters apply to the selected section.">
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <x-input name="structure_search" label="Search" wire:model.blur="search"
                     placeholder="Name or code" />
            <x-select name="structure_active_filter" label="Status" wire:model.blur="activeFilter"
                      :options="[
                          ['value' => 'all', 'label' => 'All records'],
                          ['value' => 'active', 'label' => 'Active only'],
                          ['value' => 'inactive', 'label' => 'Inactive only'],
                      ]" />
            @if($section === 'offerings')
                <x-select name="structure_offering_year" label="Academic year" wire:model.blur="offeringYearFilter"
                          :options="array_merge([['value' => '0', 'label' => 'All years']], $yearOptions)" />
            @endif
        </div>
        <div class="mt-3">
            <x-button variant="secondary" class="fs-btn-compact" wire:click="clearFilters">Clear filters</x-button>
        </div>
    </x-card>

    @if($section === 'levels')
        <div class="mt-4 grid gap-4">
            <x-card title="Educational levels" subtitle="The widest bands used by this school.">
                @if($levels === [])
                    <x-empty-state title="No educational levels"
                                   message="Add the first level for this school to start building its structure.">
                        @if($canManage)
                            <x-button variant="secondary" class="fs-btn-compact" wire:click="newLevel">Add a level</x-button>
                        @endif
                    </x-empty-state>
                @else
                    <x-table>
                        <x-slot:header>
                            <tr>
                                <th scope="col">Name</th>
                                <th scope="col">Code</th>
                                <th scope="col">Order</th>
                                <th scope="col">Status</th>
                                @if($canManage)<th scope="col"><span class="sr-only">Actions</span></th>@endif
                            </tr>
                        </x-slot:header>
                        @foreach($levels as $level)
                            <tr>
                                <td>{{ $level['name'] }}</td>
                                <td class="fs-figure">{{ $level['code'] ?? '—' }}</td>
                                <td class="fs-figure">{{ $level['display_order'] }}</td>
                                <td><x-status-pill :status="$level['is_active'] ? 'active' : 'inactive'" /></td>
                                @if($canManage)
                                    <td class="text-right">
                                        <x-button variant="secondary" class="fs-btn-compact"
                                                  wire:click="editLevel({{ $level['id'] }})"
                                                  aria-label="Edit {{ $level['name'] }}">Edit</x-button>
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </x-table>
                @endif
            </x-card>

            @if($canManage)
                <x-card :title="$editingLevelId === null ? 'New educational level' : 'Edit educational level'"
                        subtitle="Names and codes are specific to this school.">
                    <form wire:submit="saveLevel" class="grid gap-4">
                        <x-input name="level_name" label="Name" wire:model.blur="levelName" required
                                 :error="$levelErrors['name'] ?? null" />
                        <x-input name="level_code" label="Code" wire:model.blur="levelCode"
                                 help="Optional short identifier." :error="$levelErrors['code'] ?? null" />
                        <x-input name="level_order" label="Display order" type="number" min="1" max="10000"
                                 wire:model.blur="levelOrder" required
                                 :error="$levelErrors['display_order'] ?? null" />
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" wire:model.blur="levelActive" class="h-4 w-4 rounded border-control" />
                            <span>Active</span>
                        </label>
                        <div class="flex flex-wrap items-center gap-2">
                            <x-button type="submit" wire:loading.attr="disabled" wire:target="saveLevel">Save level</x-button>
                            @if($editingLevelId !== null)
                                <x-button variant="secondary" wire:click="newLevel">Cancel</x-button>
                            @endif
                            <span class="text-[13px] text-muted" wire:loading wire:target="saveLevel">Saving…</span>
                        </div>
                    </form>
                </x-card>
            @endif
        </div>
    @endif

    @if($section === 'grades')
        <div class="mt-4 grid items-start gap-4 lg:grid-cols-[minmax(0,1fr)_360px]">
            <x-card title="Grade levels" subtitle="Grades belong to one educational level.">
                @if($grades === [])
                    <x-empty-state title="No grade levels"
                                   message="Add an educational level first, then record its grade levels.">
                        @if($canManage)
                            <x-button variant="secondary" class="fs-btn-compact" wire:click="newGrade">Add a grade level</x-button>
                        @endif
                    </x-empty-state>
                @else
                    <x-table>
                        <x-slot:header>
                            <tr>
                                <th scope="col">Level</th>
                                <th scope="col">Grade</th>
                                <th scope="col">Code</th>
                                <th scope="col">Order</th>
                                <th scope="col">Status</th>
                                @if($canManage)<th scope="col"><span class="sr-only">Actions</span></th>@endif
                            </tr>
                        </x-slot:header>
                        @foreach($grades as $grade)
                            <tr>
                                <td>{{ $grade['educational_level_name'] }}</td>
                                <td>{{ $grade['name'] }}</td>
                                <td class="fs-figure">{{ $grade['code'] ?? '—' }}</td>
                                <td class="fs-figure">{{ $grade['display_order'] }}</td>
                                <td><x-status-pill :status="$grade['is_active'] ? 'active' : 'inactive'" /></td>
                                @if($canManage)
                                    <td class="text-right">
                                        <x-button variant="secondary" class="fs-btn-compact"
                                                  wire:click="editGrade({{ $grade['id'] }})"
                                                  aria-label="Edit {{ $grade['name'] }}">Edit</x-button>
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </x-table>
                @endif
            </x-card>

            @if($canManage)
                <x-card :title="$editingGradeId === null ? 'New grade level' : 'Edit grade level'"
                        subtitle="The parent level is locked to the authorised school context.">
                    @if($gradeOptions === [])
                        <x-empty-state title="No educational level" message="Add an educational level before adding a grade." />
                    @else
                        <form wire:submit="saveGrade" class="grid gap-4">
                            <x-select name="grade_level_parent" label="Educational level" wire:model.blur="gradeLevelId"
                                      :options="$gradeOptions" :error="$gradeErrors['educational_level_id'] ?? null" />
                            <x-input name="grade_name" label="Name" wire:model.blur="gradeName" required
                                     :error="$gradeErrors['name'] ?? null" />
                            <x-input name="grade_code" label="Code" wire:model.blur="gradeCode" required
                                     :error="$gradeErrors['code'] ?? null" />
                            <x-input name="grade_order" label="Display order" type="number" min="1" max="10000"
                                     wire:model.blur="gradeOrder" required
                                     :error="$gradeErrors['display_order'] ?? null" />
                            <label class="flex items-center gap-2 text-sm">
                                <input type="checkbox" wire:model.blur="gradeActive" class="h-4 w-4 rounded border-control" />
                                <span>Active</span>
                            </label>
                            <div class="flex flex-wrap items-center gap-2">
                                <x-button type="submit" wire:loading.attr="disabled" wire:target="saveGrade">Save grade</x-button>
                                @if($editingGradeId !== null)
                                    <x-button variant="secondary" wire:click="newGrade">Cancel</x-button>
                                @endif
                                <span class="text-[13px] text-muted" wire:loading wire:target="saveGrade">Saving…</span>
                            </div>
                        </form>
                    @endif
                </x-card>
            @endif
        </div>
    @endif

    @if($section === 'classes')
        <div class="mt-4 grid gap-4 lg:grid-cols-[minmax(0,1fr)_300px]">
            <x-card title="Classes" subtitle="Choose a grade level to see its class definitions.">
                @if($gradeOptions !== [])
                    <div class="mb-4 flex flex-wrap gap-2">
                        @foreach($gradeOptions as $gradeOption)
                            <button type="button" wire:click="selectGrade({{ $gradeOption['value'] }})"
                                    @class(['fs-btn fs-btn-compact',
                                            'fs-btn-primary' => (int) $gradeOption['value'] === $selectedGradeId,
                                            'fs-btn-secondary' => (int) $gradeOption['value'] !== $selectedGradeId])>
                                {{ $gradeOption['label'] }}
                            </button>
                        @endforeach
                    </div>
                @endif

                @if($classes === [])
                    <x-empty-state title="No classes"
                                   message="{{ $gradeOptions === [] ? 'Add a grade level before adding a class.' : 'This grade level has no classes yet.' }}" />
                @else
                    <x-table>
                        <x-slot:header>
                            <tr>
                                <th scope="col">Grade</th>
                                <th scope="col">Level</th>
                                <th scope="col">Name</th>
                                <th scope="col">Code</th>
                                <th scope="col">Order</th>
                                <th scope="col">Status</th>
                                @if($canManage)<th scope="col"><span class="sr-only">Actions</span></th>@endif
                            </tr>
                        </x-slot:header>
                        @foreach($classes as $class)
                            <tr>
                                <td>{{ $class['grade_level_name'] }}</td>
                                <td class="hidden md:table-cell">{{ $class['educational_level_name'] }}</td>
                                <td>{{ $class['name'] }}</td>
                                <td class="fs-figure">{{ $class['code'] ?? '—' }}</td>
                                <td class="fs-figure">{{ $class['display_order'] }}</td>
                                <td><x-status-pill :status="$class['is_active'] ? 'active' : 'inactive'" /></td>
                                @if($canManage)
                                    <td class="text-right">
                                        <x-button variant="secondary" class="fs-btn-compact"
                                                  wire:click="editClass({{ $class['id'] }})"
                                                  aria-label="Edit {{ $class['name'] }}">Edit</x-button>
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </x-table>
                @endif
            </x-card>

            @if($canManage)
                <x-card :title="$editingClassId === null ? 'New class' : 'Edit class'"
                        subtitle="Classes are defined once and can be offered in several years.">
                    @if($gradeOptions === [])
                        <x-empty-state title="No grade level" message="Add a grade level before adding a class." />
                    @else
                        <form wire:submit="saveClass" class="grid gap-4">
                            <x-select name="class_grade_parent" label="Grade level" wire:model.blur="classGradeId"
                                      :options="$gradeOptions" :error="$classErrors['grade_level_id'] ?? null" />
                            <x-input name="class_name" label="Name" wire:model.blur="className" required
                                     :error="$classErrors['name'] ?? null" />
                            <x-input name="class_code" label="Code" wire:model.blur="classCode" required
                                     :error="$classErrors['code'] ?? null" />
                            <x-input name="class_order" label="Display order" type="number" min="1" max="10000"
                                     wire:model.blur="classOrder" required
                                     :error="$classErrors['display_order'] ?? null" />
                            <label class="flex items-center gap-2 text-sm">
                                <input type="checkbox" wire:model.blur="classActive" class="h-4 w-4 rounded border-control" />
                                <span>Active</span>
                            </label>
                            <div class="flex flex-wrap items-center gap-2">
                                <x-button type="submit" wire:loading.attr="disabled" wire:target="saveClass">Save class</x-button>
                                @if($editingClassId !== null)
                                    <x-button variant="secondary" wire:click="newClass">Cancel</x-button>
                                @endif
                                <span class="text-[13px] text-muted" wire:loading wire:target="saveClass">Saving…</span>
                            </div>
                        </form>
                    @endif
                </x-card>
            @endif
        </div>
    @endif

    @if($section === 'streams')
        <div class="mt-4 grid gap-4 lg:grid-cols-[minmax(0,1fr)_300px]">
            <x-card title="Streams" subtitle="Choose a class to see its streams.">
                @if($classOptions !== [])
                    <div class="mb-4 flex flex-wrap gap-2">
                        @foreach($classOptions as $classOption)
                            <button type="button" wire:click="selectClass({{ $classOption['value'] }})"
                                    @class(['fs-btn fs-btn-compact',
                                            'fs-btn-primary' => (int) $classOption['value'] === $selectedClassId,
                                            'fs-btn-secondary' => (int) $classOption['value'] !== $selectedClassId])>
                                {{ $classOption['label'] }}
                            </button>
                        @endforeach
                    </div>
                @endif

                @if($streams === [])
                    <x-empty-state title="No streams"
                                   message="{{ $classOptions === [] ? 'Add a class before adding a stream.' : 'This class has no streams yet.' }}" />
                @else
                    <x-table>
                        <x-slot:header>
                            <tr>
                                <th scope="col">Class</th>
                                <th scope="col">Grade</th>
                                <th scope="col">Name</th>
                                <th scope="col">Code</th>
                                <th scope="col">Order</th>
                                <th scope="col">Status</th>
                                @if($canManage)<th scope="col"><span class="sr-only">Actions</span></th>@endif
                            </tr>
                        </x-slot:header>
                        @foreach($streams as $stream)
                            <tr>
                                <td>{{ $stream['class_name'] }}</td>
                                <td class="hidden md:table-cell">{{ $stream['grade_level_name'] }}</td>
                                <td>{{ $stream['name'] }}</td>
                                <td class="fs-figure">{{ $stream['code'] ?? '—' }}</td>
                                <td class="fs-figure">{{ $stream['display_order'] }}</td>
                                <td><x-status-pill :status="$stream['is_active'] ? 'active' : 'inactive'" /></td>
                                @if($canManage)
                                    <td class="text-right">
                                        <x-button variant="secondary" class="fs-btn-compact"
                                                  wire:click="editStream({{ $stream['id'] }})"
                                                  aria-label="Edit {{ $stream['name'] }}">Edit</x-button>
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </x-table>
                @endif
            </x-card>

            @if($canManage)
                <x-card :title="$editingStreamId === null ? 'New stream' : 'Edit stream'"
                        subtitle="Streams belong to one class definition.">
                    @if($classOptions === [])
                        <x-empty-state title="No class" message="Add a class before adding a stream." />
                    @else
                        <form wire:submit="saveStream" class="grid gap-4">
                            <x-select name="stream_class_parent" label="Class" wire:model.blur="streamClassId"
                                      :options="$classOptions" :error="$streamErrors['class_definition_id'] ?? null" />
                            <x-input name="stream_name" label="Name" wire:model.blur="streamName" required
                                     :error="$streamErrors['name'] ?? null" />
                            <x-input name="stream_code" label="Code" wire:model.blur="streamCode" required
                                     :error="$streamErrors['code'] ?? null" />
                            <x-input name="stream_order" label="Display order" type="number" min="1" max="10000"
                                     wire:model.blur="streamOrder" required
                                     :error="$streamErrors['display_order'] ?? null" />
                            <label class="flex items-center gap-2 text-sm">
                                <input type="checkbox" wire:model.blur="streamActive" class="h-4 w-4 rounded border-control" />
                                <span>Active</span>
                            </label>
                            <div class="flex flex-wrap items-center gap-2">
                                <x-button type="submit" wire:loading.attr="disabled" wire:target="saveStream">Save stream</x-button>
                                @if($editingStreamId !== null)
                                    <x-button variant="secondary" wire:click="newStream">Cancel</x-button>
                                @endif
                                <span class="text-[13px] text-muted" wire:loading wire:target="saveStream">Saving…</span>
                            </div>
                        </form>
                    @endif
                </x-card>
            @endif
        </div>
    @endif

    @if($section === 'offerings')
        <div class="mt-4 grid items-start gap-4 lg:grid-cols-[minmax(0,1fr)_360px]">
            <x-card title="Class offerings" subtitle="Classes offered in a specific academic year.">
                @if($offerings === [])
                    <x-empty-state title="No class offerings"
                                   message="{{ $years === [] ? 'Add an academic year in the calendar before offering a class.' : 'No offerings match this filter yet.' }}" />
                @else
                    <x-table>
                        <x-slot:header>
                            <tr>
                                <th scope="col">Year</th>
                                <th scope="col">Class</th>
                                <th scope="col">Grade</th>
                                <th scope="col">Name</th>
                                <th scope="col">Status</th>
                                @if($canManage)<th scope="col"><span class="sr-only">Actions</span></th>@endif
                            </tr>
                        </x-slot:header>
                        @foreach($offerings as $offering)
                            <tr>
                                <td class="fs-figure">{{ $offering['academic_year_name'] }}</td>
                                <td>{{ $offering['class_name'] }}</td>
                                <td class="hidden md:table-cell">{{ $offering['grade_level_name'] }}</td>
                                <td>{{ $offering['name'] ?? '—' }}</td>
                                <td><x-status-pill :status="$offering['is_active'] ? 'active' : 'inactive'" /></td>
                                @if($canManage)
                                    <td class="text-right">
                                        <x-button variant="secondary" class="fs-btn-compact"
                                                  wire:click="editOffering({{ $offering['id'] }})"
                                                  aria-label="Edit offering for {{ $offering['class_name'] }}">Edit</x-button>
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </x-table>
                @endif
            </x-card>

            @if($canManage)
                <x-card :title="$editingOfferingId === null ? 'New class offering' : 'Edit class offering'"
                        subtitle="Both a class and an academic year are required.">
                    @if($classOptions === [] || $yearOptions === [])
                        <x-empty-state title="Missing parents"
                                       message="Add a class and an academic year before creating an offering." />
                    @else
                        <form wire:submit="saveOffering" class="grid gap-4">
                            <x-select name="offering_class" label="Class" wire:model.blur="offeringClassId"
                                      :options="$classOptions" :error="$offeringErrors['class_definition_id'] ?? null" />
                            <x-select name="offering_year" label="Academic year" wire:model.blur="offeringYearId"
                                      :options="$yearOptions" :error="$offeringErrors['academic_year_id'] ?? null" />
                            <x-input name="offering_name" label="Name" wire:model.blur="offeringName"
                                     help="Optional label for this offering."
                                     :error="$offeringErrors['name'] ?? null" />
                            <label class="flex items-center gap-2 text-sm">
                                <input type="checkbox" wire:model.blur="offeringActive" class="h-4 w-4 rounded border-control" />
                                <span>Active</span>
                            </label>
                            <div class="flex flex-wrap items-center gap-2">
                                <x-button type="submit" wire:loading.attr="disabled" wire:target="saveOffering">Save offering</x-button>
                                @if($editingOfferingId !== null)
                                    <x-button variant="secondary" wire:click="newOffering">Cancel</x-button>
                                @endif
                                <span class="text-[13px] text-muted" wire:loading wire:target="saveOffering">Saving…</span>
                            </div>
                        </form>
                    @endif
                </x-card>
            @endif
        </div>
    @endif
</x-app-shell>
