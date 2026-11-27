<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Frontend\Contracts\AcademicStructureGateway;
use App\Frontend\Contracts\FrontendDataProvider;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Academic structure')]
final class AcademicStructure extends Component
{
    private const SECTIONS = ['levels', 'grades', 'classes', 'streams', 'offerings'];

    public string $section = 'levels';

    public string $search = '';

    public string $activeFilter = 'all';

    public int $selectedGradeId = 0;

    public int $selectedClassId = 0;

    public int $offeringYearFilter = 0;

    public string $levelName = '';

    public string $levelCode = '';

    public string $levelOrder = '1';

    public bool $levelActive = true;

    public ?int $editingLevelId = null;

    /** @var array<string, string> */
    public array $levelErrors = [];

    public string $gradeLevelId = '0';

    public string $gradeName = '';

    public string $gradeCode = '';

    public string $gradeOrder = '1';

    public bool $gradeActive = true;

    public ?int $editingGradeId = null;

    /** @var array<string, string> */
    public array $gradeErrors = [];

    public string $classGradeId = '0';

    public string $className = '';

    public string $classCode = '';

    public string $classOrder = '1';

    public bool $classActive = true;

    public ?int $editingClassId = null;

    /** @var array<string, string> */
    public array $classErrors = [];

    public string $streamClassId = '0';

    public string $streamName = '';

    public string $streamCode = '';

    public string $streamOrder = '1';

    public bool $streamActive = true;

    public ?int $editingStreamId = null;

    /** @var array<string, string> */
    public array $streamErrors = [];

    public string $offeringClassId = '0';

    public string $offeringYearId = '0';

    public string $offeringName = '';

    public bool $offeringActive = true;

    public ?int $editingOfferingId = null;

    /** @var array<string, string> */
    public array $offeringErrors = [];

    public string $formError = '';

    public string $successMessage = '';

    public function render(AcademicStructureGateway $gateway, FrontendDataProvider $data): View
    {
        $levels = $gateway->levels();
        $grades = $gateway->gradeLevels();
        $years = $gateway->years();
        $allClasses = $gateway->allClasses();
        $allStreams = $gateway->allStreams();
        $this->resolveSelections($grades, $allClasses, $years);

        $classes = array_values(array_filter(
            $allClasses,
            fn (array $class): bool => (int) $class['grade_level_id'] === $this->selectedGradeId,
        ));
        $streams = array_values(array_filter(
            $allStreams,
            fn (array $stream): bool => (int) $stream['class_definition_id'] === $this->selectedClassId,
        ));
        $offerings = $gateway->offerings($this->offeringYearFilter > 0 ? $this->offeringYearFilter : null);

        return view('livewire.academic-structure', [
            'user' => $data->user(),
            'school' => $data->school(),
            'canManage' => $gateway->canManage(),
            'sections' => $this->sectionLabels(),
            'counts' => [
                'levels' => count($levels),
                'grades' => count($grades),
                'classes' => count($allClasses),
                'streams' => count($allStreams),
                'offerings' => count($offerings),
            ],
            'levels' => $this->filterRecords($levels),
            'allLevels' => $levels,
            'grades' => $this->filterRecords($grades),
            'gradeOptions' => $this->optionsFromRecords($grades),
            'classes' => $this->filterRecords($classes),
            'allClasses' => $allClasses,
            'classOptions' => $this->optionsFromRecords($allClasses),
            'streams' => $this->filterRecords($streams),
            'offerings' => $this->filterRecords($offerings),
            'years' => $years,
            'yearOptions' => $this->optionsFromRecords($years),
        ]);
    }

    public function selectSection(string $section): void
    {
        if (! in_array($section, self::SECTIONS, true)) {
            return;
        }

        $this->section = $section;
        $this->clearMessages();
    }

    public function selectGrade(int $gradeId): void
    {
        $this->selectedGradeId = $gradeId;
        $this->resetClassForm();
        $this->clearMessages();
    }

    public function selectClass(int $classId): void
    {
        $this->selectedClassId = $classId;
        $this->resetStreamForm();
        $this->clearMessages();
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->activeFilter = 'all';
        $this->offeringYearFilter = 0;
    }

    public function newLevel(): void
    {
        $this->resetLevelForm();
        $this->clearMessages();
    }

    public function editLevel(int $levelId): void
    {
        $record = $this->findRecord($this->gateway()->levels(), $levelId);

        if ($record === null) {
            $this->formError = 'That educational level is not available in this school context.';

            return;
        }

        $this->editingLevelId = $levelId;
        $this->levelName = (string) $record['name'];
        $this->levelCode = (string) ($record['code'] ?? '');
        $this->levelOrder = (string) $record['display_order'];
        $this->levelActive = (bool) $record['is_active'];
        $this->levelErrors = [];
        $this->clearMessages();
    }

    public function saveLevel(): void
    {
        $wasEditing = $this->editingLevelId !== null;
        $this->levelErrors = [];
        $this->clearMessages();

        try {
            $wasEditing
                ? $this->gateway()->updateLevel((int) $this->editingLevelId, $this->levelData())
                : $this->gateway()->createLevel($this->levelData());
        } catch (ValidationException $exception) {
            $this->levelErrors = $this->validationErrors($exception);
            $this->formError = 'Check the highlighted fields and try again.';

            return;
        } catch (AuthorizationException $exception) {
            $this->formError = $exception->getMessage();

            return;
        } catch (ModelNotFoundException) {
            $this->formError = 'That educational level is no longer available in this school context.';

            return;
        }

        $this->resetLevelForm();
        $this->successMessage = $wasEditing ? 'Educational level updated.' : 'Educational level added.';

        if (! $wasEditing) {
            $this->section = 'levels';
        }
    }

    public function newGrade(): void
    {
        $this->resetGradeForm();
        $this->clearMessages();
    }

    public function editGrade(int $gradeId): void
    {
        $record = $this->findRecord($this->gateway()->gradeLevels(), $gradeId);

        if ($record === null) {
            $this->formError = 'That grade level is not available in this school context.';

            return;
        }

        $this->editingGradeId = $gradeId;
        $this->gradeLevelId = (string) $record['educational_level_id'];
        $this->gradeName = (string) $record['name'];
        $this->gradeCode = (string) ($record['code'] ?? '');
        $this->gradeOrder = (string) $record['display_order'];
        $this->gradeActive = (bool) $record['is_active'];
        $this->gradeErrors = [];
        $this->clearMessages();
    }

    public function saveGrade(): void
    {
        if ($this->gradeLevelId === '0') {
            $this->formError = 'Add or select an educational level before saving a grade level.';

            return;
        }

        $wasEditing = $this->editingGradeId !== null;
        $this->gradeErrors = [];
        $this->clearMessages();

        try {
            $wasEditing
                ? $this->gateway()->updateGrade((int) $this->editingGradeId, $this->gradeData())
                : $this->gateway()->createGrade((int) $this->gradeLevelId, $this->gradeData());
        } catch (ValidationException $exception) {
            $this->gradeErrors = $this->validationErrors($exception);
            $this->formError = 'Check the highlighted fields and try again.';

            return;
        } catch (AuthorizationException $exception) {
            $this->formError = $exception->getMessage();

            return;
        } catch (ModelNotFoundException) {
            $this->formError = 'That grade level or its parent is no longer available in this school context.';

            return;
        }

        $this->resetGradeForm();
        $this->successMessage = $wasEditing ? 'Grade level updated.' : 'Grade level added.';
    }

    public function newClass(): void
    {
        $this->resetClassForm();
        $this->clearMessages();
    }

    public function editClass(int $classId): void
    {
        $gateway = $this->gateway();
        $record = null;

        foreach ($gateway->allClasses() as $candidate) {
            if ((int) $candidate['id'] === $classId) {
                $record = $candidate;
                break;
            }
        }

        if ($record === null) {
            $this->formError = 'That class is not available in this school context.';

            return;
        }

        $this->selectedGradeId = (int) $record['grade_level_id'];
        $this->editingClassId = $classId;
        $this->classGradeId = (string) $record['grade_level_id'];
        $this->className = (string) $record['name'];
        $this->classCode = (string) ($record['code'] ?? '');
        $this->classOrder = (string) $record['display_order'];
        $this->classActive = (bool) $record['is_active'];
        $this->classErrors = [];
        $this->clearMessages();
    }

    public function saveClass(): void
    {
        if ($this->classGradeId === '0') {
            $this->formError = 'Add or select a grade level before saving a class.';

            return;
        }

        $wasEditing = $this->editingClassId !== null;
        $this->classErrors = [];
        $this->clearMessages();

        try {
            $wasEditing
                ? $this->gateway()->updateClass((int) $this->editingClassId, $this->classData())
                : $this->gateway()->createClass((int) $this->classGradeId, $this->classData());
        } catch (ValidationException $exception) {
            $this->classErrors = $this->validationErrors($exception);
            $this->formError = 'Check the highlighted fields and try again.';

            return;
        } catch (AuthorizationException $exception) {
            $this->formError = $exception->getMessage();

            return;
        } catch (ModelNotFoundException) {
            $this->formError = 'That class or its parent is no longer available in this school context.';

            return;
        }

        $this->resetClassForm();
        $this->successMessage = $wasEditing ? 'Class updated.' : 'Class added.';
    }

    public function newStream(): void
    {
        $this->resetStreamForm();
        $this->clearMessages();
    }

    public function editStream(int $streamId): void
    {
        $gateway = $this->gateway();
        $record = null;

        foreach ($gateway->allStreams() as $candidate) {
            if ((int) $candidate['id'] === $streamId) {
                $record = $candidate;
                break;
            }
        }

        if ($record === null) {
            $this->formError = 'That stream is not available in this school context.';

            return;
        }

        $this->selectedClassId = (int) $record['class_definition_id'];
        $this->editingStreamId = $streamId;
        $this->streamClassId = (string) $record['class_definition_id'];
        $this->streamName = (string) $record['name'];
        $this->streamCode = (string) ($record['code'] ?? '');
        $this->streamOrder = (string) $record['display_order'];
        $this->streamActive = (bool) $record['is_active'];
        $this->streamErrors = [];
        $this->clearMessages();
    }

    public function saveStream(): void
    {
        if ($this->streamClassId === '0') {
            $this->formError = 'Add or select a class before saving a stream.';

            return;
        }

        $wasEditing = $this->editingStreamId !== null;
        $this->streamErrors = [];
        $this->clearMessages();

        try {
            $wasEditing
                ? $this->gateway()->updateStream((int) $this->editingStreamId, $this->streamData())
                : $this->gateway()->createStream((int) $this->streamClassId, $this->streamData());
        } catch (ValidationException $exception) {
            $this->streamErrors = $this->validationErrors($exception);
            $this->formError = 'Check the highlighted fields and try again.';

            return;
        } catch (AuthorizationException $exception) {
            $this->formError = $exception->getMessage();

            return;
        } catch (ModelNotFoundException) {
            $this->formError = 'That stream or its parent is no longer available in this school context.';

            return;
        }

        $this->resetStreamForm();
        $this->successMessage = $wasEditing ? 'Stream updated.' : 'Stream added.';
    }

    public function newOffering(): void
    {
        $this->resetOfferingForm();
        $this->clearMessages();
    }

    public function editOffering(int $offeringId): void
    {
        $record = $this->findRecord($this->gateway()->offerings(), $offeringId);

        if ($record === null) {
            $this->formError = 'That class offering is not available in this school context.';

            return;
        }

        $this->offeringYearFilter = (int) $record['academic_year_id'];
        $this->editingOfferingId = $offeringId;
        $this->offeringClassId = (string) $record['class_definition_id'];
        $this->offeringYearId = (string) $record['academic_year_id'];
        $this->offeringName = (string) ($record['name'] ?? '');
        $this->offeringActive = (bool) $record['is_active'];
        $this->offeringErrors = [];
        $this->clearMessages();
    }

    public function saveOffering(): void
    {
        if ($this->offeringClassId === '0' || $this->offeringYearId === '0') {
            $this->formError = 'Select both a class and an academic year before saving an offering.';

            return;
        }

        $wasEditing = $this->editingOfferingId !== null;
        $this->offeringErrors = [];
        $this->clearMessages();

        try {
            $wasEditing
                ? $this->gateway()->updateOffering((int) $this->editingOfferingId, $this->offeringData())
                : $this->gateway()->createOffering($this->offeringData());
        } catch (ValidationException $exception) {
            $this->offeringErrors = $this->validationErrors($exception);
            $this->formError = 'Check the highlighted fields and try again.';

            return;
        } catch (AuthorizationException $exception) {
            $this->formError = $exception->getMessage();

            return;
        } catch (ModelNotFoundException) {
            $this->formError = 'That offering or its parent is no longer available in this school context.';

            return;
        }

        $this->resetOfferingForm();
        $this->successMessage = $wasEditing ? 'Class offering updated.' : 'Class offering added.';
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private function sectionLabels(): array
    {
        return [
            ['value' => 'levels', 'label' => 'Levels'],
            ['value' => 'grades', 'label' => 'Grade levels'],
            ['value' => 'classes', 'label' => 'Classes'],
            ['value' => 'streams', 'label' => 'Streams'],
            ['value' => 'offerings', 'label' => 'Class offerings'],
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $grades
     * @param  array<int, array<string, mixed>>  $classes
     * @param  array<int, array<string, mixed>>  $years
     */
    private function resolveSelections(array $grades, array $classes, array $years): void
    {
        $gradeIds = array_map(static fn (array $grade): int => (int) $grade['id'], $grades);
        $classIds = array_map(static fn (array $class): int => (int) $class['id'], $classes);
        $yearIds = array_map(static fn (array $year): int => (int) $year['id'], $years);

        if (! in_array($this->selectedGradeId, $gradeIds, true)) {
            $this->selectedGradeId = $gradeIds[0] ?? 0;
        }

        if (! in_array($this->selectedClassId, $classIds, true)) {
            $this->selectedClassId = $classIds[0] ?? 0;
        }

        if ($this->offeringYearFilter !== 0 && ! in_array($this->offeringYearFilter, $yearIds, true)) {
            $this->offeringYearFilter = 0;
        }

        if ($this->gradeLevelId === '0' && $gradeIds !== []) {
            $this->gradeLevelId = (string) $gradeIds[0];
        }

        if ($this->classGradeId === '0' && $gradeIds !== []) {
            $this->classGradeId = (string) $gradeIds[0];
        }

        if ($this->streamClassId === '0' && $classIds !== []) {
            $this->streamClassId = (string) $classIds[0];
        }

        if ($this->offeringClassId === '0' && $classIds !== []) {
            $this->offeringClassId = (string) $classIds[0];
        }

        if ($this->offeringYearId === '0' && $yearIds !== []) {
            $this->offeringYearId = (string) $yearIds[0];
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $records
     * @return array<int, array<string, mixed>>
     */
    private function filterRecords(array $records): array
    {
        $needle = mb_strtolower(trim($this->search));

        return array_values(array_filter($records, function (array $record) use ($needle): bool {
            $active = (bool) ($record['is_active'] ?? false);

            if ($this->activeFilter === 'active' && ! $active) {
                return false;
            }

            if ($this->activeFilter === 'inactive' && $active) {
                return false;
            }

            if ($needle === '') {
                return true;
            }

            foreach (['name', 'code', 'educational_level_name', 'grade_level_name', 'class_name', 'academic_year_name'] as $field) {
                $value = $record[$field] ?? null;

                if (is_scalar($value) && str_contains(mb_strtolower((string) $value), $needle)) {
                    return true;
                }
            }

            return false;
        }));
    }

    /**
     * @param  array<int, array<string, mixed>>  $records
     * @return array<int, array{value: string, label: string}>
     */
    private function optionsFromRecords(array $records): array
    {
        $options = [];

        foreach ($records as $record) {
            $label = (string) ($record['name'] ?? '');

            foreach (['educational_level_name', 'grade_level_name', 'class_name'] as $field) {
                $parent = $record[$field] ?? null;

                if (is_string($parent) && $parent !== '' && $parent !== '—') {
                    $label .= ' · '.$parent;
                    break;
                }
            }

            $options[] = [
                'value' => (string) $record['id'],
                'label' => $label,
            ];
        }

        return $options;
    }

    /**
     * @param  array<int, array<string, mixed>>  $records
     * @return array<string, mixed>|null
     */
    private function findRecord(array $records, int $id): ?array
    {
        foreach ($records as $record) {
            if ((int) $record['id'] === $id) {
                return $record;
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private function levelData(): array
    {
        return [
            'name' => trim($this->levelName),
            'code' => $this->nullable($this->levelCode),
            'display_order' => trim($this->levelOrder),
            'is_active' => $this->levelActive,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function gradeData(): array
    {
        return [
            'educational_level_id' => (int) $this->gradeLevelId,
            'name' => trim($this->gradeName),
            'code' => $this->nullable($this->gradeCode),
            'display_order' => trim($this->gradeOrder),
            'is_active' => $this->gradeActive,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function classData(): array
    {
        return [
            'grade_level_id' => (int) $this->classGradeId,
            'name' => trim($this->className),
            'code' => $this->nullable($this->classCode),
            'display_order' => trim($this->classOrder),
            'is_active' => $this->classActive,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function streamData(): array
    {
        return [
            'class_definition_id' => (int) $this->streamClassId,
            'name' => trim($this->streamName),
            'code' => $this->nullable($this->streamCode),
            'display_order' => trim($this->streamOrder),
            'is_active' => $this->streamActive,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function offeringData(): array
    {
        return [
            'class_definition_id' => (int) $this->offeringClassId,
            'academic_year_id' => (int) $this->offeringYearId,
            'name' => $this->nullable($this->offeringName),
            'is_active' => $this->offeringActive,
        ];
    }

    private function nullable(string $value): ?string
    {
        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }

    private function resetLevelForm(): void
    {
        $this->editingLevelId = null;
        $this->levelName = '';
        $this->levelCode = '';
        $this->levelOrder = '1';
        $this->levelActive = true;
        $this->levelErrors = [];
    }

    private function resetGradeForm(): void
    {
        $this->editingGradeId = null;
        $this->gradeName = '';
        $this->gradeCode = '';
        $this->gradeOrder = '1';
        $this->gradeActive = true;
        $this->gradeErrors = [];
    }

    private function resetClassForm(): void
    {
        $this->editingClassId = null;
        $this->className = '';
        $this->classCode = '';
        $this->classOrder = '1';
        $this->classActive = true;
        $this->classErrors = [];
    }

    private function resetStreamForm(): void
    {
        $this->editingStreamId = null;
        $this->streamName = '';
        $this->streamCode = '';
        $this->streamOrder = '1';
        $this->streamActive = true;
        $this->streamErrors = [];
    }

    private function resetOfferingForm(): void
    {
        $this->editingOfferingId = null;
        $this->offeringName = '';
        $this->offeringActive = true;
        $this->offeringErrors = [];
    }

    private function clearMessages(): void
    {
        $this->formError = '';
        $this->successMessage = '';
    }

    /**
     * @return array<string, string>
     */
    private function validationErrors(ValidationException $exception): array
    {
        $errors = [];

        foreach ($exception->validator->errors()->messages() as $field => $messages) {
            $errors[(string) $field] = (string) ($messages[0] ?? 'This value is not valid.');
        }

        return $errors;
    }

    private function gateway(): AcademicStructureGateway
    {
        /** @var AcademicStructureGateway $gateway */
        $gateway = app(AcademicStructureGateway::class);

        return $gateway;
    }
}
