<?php

declare(strict_types=1);

namespace App\Frontend;

use App\Domains\AcademicCalendar\Models\AcademicYear;
use App\Domains\AcademicStructure\Actions\CreateClassDefinition;
use App\Domains\AcademicStructure\Actions\CreateClassOffering;
use App\Domains\AcademicStructure\Actions\CreateEducationalLevel;
use App\Domains\AcademicStructure\Actions\CreateGradeLevel;
use App\Domains\AcademicStructure\Actions\CreateStream;
use App\Domains\AcademicStructure\Actions\UpdateClassDefinition;
use App\Domains\AcademicStructure\Actions\UpdateClassOffering;
use App\Domains\AcademicStructure\Actions\UpdateEducationalLevel;
use App\Domains\AcademicStructure\Actions\UpdateGradeLevel;
use App\Domains\AcademicStructure\Actions\UpdateStream;
use App\Domains\AcademicStructure\Models\ClassDefinition;
use App\Domains\AcademicStructure\Models\ClassOffering;
use App\Domains\AcademicStructure\Models\EducationalLevel;
use App\Domains\AcademicStructure\Models\GradeLevel;
use App\Domains\AcademicStructure\Models\Stream;
use App\Domains\Authorization\Services\SchoolPermissions;
use App\Domains\School\Context\SchoolContext;
use App\Domains\School\Models\School;
use App\Frontend\Contracts\AcademicStructureGateway;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/**
 * Adapter over the verified Academic Structure domain actions.
 *
 * Reads mirror the JSON API's school-scoped queries. Writes call the same
 * actions as the API, so validation, audit and integrity checks stay in one
 * place. Extra display labels are frontend conveniences, not API fields.
 */
final class DomainAcademicStructureGateway implements AcademicStructureGateway
{
    public function __construct(
        private readonly SchoolContext $context,
        private readonly SchoolPermissions $permissions,
        private readonly CreateEducationalLevel $createLevelAction,
        private readonly UpdateEducationalLevel $updateLevelAction,
        private readonly CreateGradeLevel $createGradeAction,
        private readonly UpdateGradeLevel $updateGradeAction,
        private readonly CreateClassDefinition $createClassAction,
        private readonly UpdateClassDefinition $updateClassAction,
        private readonly CreateStream $createStreamAction,
        private readonly UpdateStream $updateStreamAction,
        private readonly CreateClassOffering $createOfferingAction,
        private readonly UpdateClassOffering $updateOfferingAction,
    ) {}

    private ?bool $viewAllowed = null;

    private ?bool $manageAllowed = null;

    public function canView(): bool
    {
        if ($this->viewAllowed !== null) {
            return $this->viewAllowed;
        }

        return $this->viewAllowed = $this->hasPermission('academic_structure.view');
    }

    public function canManage(): bool
    {
        if ($this->manageAllowed !== null) {
            return $this->manageAllowed;
        }

        return $this->manageAllowed = $this->hasPermission('academic_structure.manage');
    }

    public function levels(): array
    {
        $this->assertCanView();

        return EducationalLevel::query()
            ->forCurrentSchool()
            ->orderBy('display_order')
            ->orderBy('id')
            ->get()
            ->map(fn (EducationalLevel $level): array => $this->levelPayload($level))
            ->all();
    }

    public function createLevel(array $data): array
    {
        $this->assertCanManage();

        return $this->levelPayload($this->createLevelAction->execute(
            $this->school(),
            $this->withoutClientTenancy($data),
        ));
    }

    public function updateLevel(int $educationalLevelId, array $data): array
    {
        $this->assertCanManage();

        return $this->levelPayload($this->updateLevelAction->execute(
            $this->school(),
            $this->level($educationalLevelId),
            $this->withoutClientTenancy($data),
        ));
    }

    public function allClasses(): array
    {
        $this->assertCanView();

        return ClassDefinition::query()
            ->with('gradeLevel.educationalLevel')
            ->forCurrentSchool()
            ->orderBy('display_order')
            ->orderBy('id')
            ->get()
            ->map(fn (ClassDefinition $class): array => $this->classPayload($class))
            ->all();
    }

    public function gradeLevels(): array
    {
        $this->assertCanView();

        return GradeLevel::query()
            ->with('educationalLevel')
            ->forCurrentSchool()
            ->orderBy('display_order')
            ->orderBy('id')
            ->get()
            ->map(fn (GradeLevel $grade): array => $this->gradePayload($grade))
            ->all();
    }

    public function createGrade(int $educationalLevelId, array $data): array
    {
        $this->assertCanManage();
        $level = $this->level($educationalLevelId);
        $data = $this->withoutClientTenancy($data);
        unset($data['educational_level_id']);
        $data['educational_level_id'] = (int) $level->id;

        return $this->gradePayload($this->createGradeAction->execute($this->school(), $data));
    }

    public function updateGrade(int $gradeLevelId, array $data): array
    {
        $this->assertCanManage();

        return $this->gradePayload($this->updateGradeAction->execute(
            $this->school(),
            $this->grade($gradeLevelId),
            $this->withoutClientTenancy($data),
        ));
    }

    public function classes(int $gradeLevelId): array
    {
        $this->assertCanView();
        $grade = $this->grade($gradeLevelId);

        return $grade->classes()
            ->with('gradeLevel.educationalLevel')
            ->forCurrentSchool()
            ->orderBy('display_order')
            ->orderBy('id')
            ->get()
            ->map(fn (ClassDefinition $class): array => $this->classPayload($class))
            ->all();
    }

    public function createClass(int $gradeLevelId, array $data): array
    {
        $this->assertCanManage();
        $grade = $this->grade($gradeLevelId);
        $data = $this->withoutClientTenancy($data);
        unset($data['grade_level_id']);
        $data['grade_level_id'] = (int) $grade->id;

        return $this->classPayload($this->createClassAction->execute($this->school(), $data));
    }

    public function updateClass(int $classDefinitionId, array $data): array
    {
        $this->assertCanManage();

        return $this->classPayload($this->updateClassAction->execute(
            $this->school(),
            $this->classDefinition($classDefinitionId),
            $this->withoutClientTenancy($data),
        ));
    }

    public function allStreams(): array
    {
        $this->assertCanView();

        return Stream::query()
            ->with('classDefinition.gradeLevel')
            ->forCurrentSchool()
            ->orderBy('display_order')
            ->orderBy('id')
            ->get()
            ->map(fn (Stream $stream): array => $this->streamPayload($stream))
            ->all();
    }

    public function streams(int $classDefinitionId): array
    {
        $this->assertCanView();
        $class = $this->classDefinition($classDefinitionId);

        return $class->streams()
            ->with('classDefinition.gradeLevel')
            ->forCurrentSchool()
            ->orderBy('display_order')
            ->orderBy('id')
            ->get()
            ->map(fn (Stream $stream): array => $this->streamPayload($stream))
            ->all();
    }

    public function createStream(int $classDefinitionId, array $data): array
    {
        $this->assertCanManage();
        $class = $this->classDefinition($classDefinitionId);
        $data = $this->withoutClientTenancy($data);
        unset($data['class_definition_id']);
        $data['class_definition_id'] = (int) $class->id;

        return $this->streamPayload($this->createStreamAction->execute($this->school(), $data));
    }

    public function updateStream(int $streamId, array $data): array
    {
        $this->assertCanManage();

        return $this->streamPayload($this->updateStreamAction->execute(
            $this->school(),
            $this->stream($streamId),
            $this->withoutClientTenancy($data),
        ));
    }

    public function years(): array
    {
        $this->assertCanView();

        return AcademicYear::query()
            ->forCurrentSchool()
            ->orderBy('start_date')
            ->orderBy('id')
            ->get()
            ->map(fn (AcademicYear $year): array => [
                'id' => (int) $year->id,
                'name' => (string) $year->name,
                'start_date' => (string) $year->start_date,
                'end_date' => (string) $year->end_date,
                'status' => (string) $year->status,
            ])
            ->all();
    }

    public function offerings(?int $academicYearId = null): array
    {
        $this->assertCanView();

        $query = ClassOffering::query()
            ->with(['classDefinition.gradeLevel', 'academicYear'])
            ->forCurrentSchool();

        if ($academicYearId !== null && $academicYearId > 0) {
            $year = $this->year($academicYearId);
            $query->where('academic_year_id', $year->id);
        }

        return $query
            ->orderBy('id')
            ->get()
            ->map(fn (ClassOffering $offering): array => $this->offeringPayload($offering))
            ->all();
    }

    public function createOffering(array $data): array
    {
        $this->assertCanManage();

        return $this->offeringPayload($this->createOfferingAction->execute(
            $this->school(),
            $this->withoutClientTenancy($data),
        ));
    }

    public function updateOffering(int $classOfferingId, array $data): array
    {
        $this->assertCanManage();

        return $this->offeringPayload($this->updateOfferingAction->execute(
            $this->school(),
            $this->offering($classOfferingId),
            $this->withoutClientTenancy($data),
        ));
    }

    private function hasPermission(string $permission): bool
    {
        $user = Auth::user();
        $school = $this->context->current();

        if (! $user instanceof User || $school === null) {
            return false;
        }

        return $this->permissions->has($user, $school, $permission);
    }

    private function assertCanView(): void
    {
        if (! $this->canView()) {
            throw new AuthorizationException('You do not have permission to view academic structure.');
        }
    }

    private function assertCanManage(): void
    {
        if (! $this->canManage()) {
            throw new AuthorizationException('You do not have permission to change academic structure.');
        }
    }

    private function school(): School
    {
        return $this->context->assertCurrent();
    }

    private function level(int $id): EducationalLevel
    {
        /** @var EducationalLevel $level */
        $level = EducationalLevel::query()->forCurrentSchool()->findOrFail($id);

        return $level;
    }

    private function grade(int $id): GradeLevel
    {
        /** @var GradeLevel $grade */
        $grade = GradeLevel::query()
            ->with('educationalLevel')
            ->forCurrentSchool()
            ->findOrFail($id);

        return $grade;
    }

    private function classDefinition(int $id): ClassDefinition
    {
        /** @var ClassDefinition $class */
        $class = ClassDefinition::query()
            ->with('gradeLevel.educationalLevel')
            ->forCurrentSchool()
            ->findOrFail($id);

        return $class;
    }

    private function stream(int $id): Stream
    {
        /** @var Stream $stream */
        $stream = Stream::query()
            ->with('classDefinition.gradeLevel')
            ->forCurrentSchool()
            ->findOrFail($id);

        return $stream;
    }

    private function year(int $id): AcademicYear
    {
        /** @var AcademicYear $year */
        $year = AcademicYear::query()->forCurrentSchool()->findOrFail($id);

        return $year;
    }

    private function offering(int $id): ClassOffering
    {
        /** @var ClassOffering $offering */
        $offering = ClassOffering::query()
            ->with(['classDefinition.gradeLevel', 'academicYear'])
            ->forCurrentSchool()
            ->findOrFail($id);

        return $offering;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function withoutClientTenancy(array $data): array
    {
        if (array_key_exists('school_id', $data)) {
            throw ValidationException::withMessages([
                'school_id' => 'The school id field is prohibited.',
            ]);
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    private function levelPayload(EducationalLevel $level): array
    {
        $configuration = $level->configuration;

        return [
            'id' => (int) $level->id,
            'name' => (string) $level->name,
            'code' => $level->code === null ? null : (string) $level->code,
            'display_order' => (int) $level->display_order,
            'is_active' => (bool) $level->is_active,
            'configuration_present' => is_array($configuration) && $configuration !== [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function gradePayload(GradeLevel $grade): array
    {
        $level = $grade->educationalLevel;

        return [
            'id' => (int) $grade->id,
            'educational_level_id' => (int) $grade->educational_level_id,
            'educational_level_name' => $level === null ? '—' : (string) $level->name,
            'name' => (string) $grade->name,
            'code' => $grade->code === null ? null : (string) $grade->code,
            'display_order' => (int) $grade->display_order,
            'is_active' => (bool) $grade->is_active,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function classPayload(ClassDefinition $class): array
    {
        $grade = $class->gradeLevel;
        $level = $grade?->educationalLevel;
        $configuration = $class->configuration;

        return [
            'id' => (int) $class->id,
            'grade_level_id' => (int) $class->grade_level_id,
            'grade_level_name' => $grade === null ? '—' : (string) $grade->name,
            'educational_level_name' => $level === null ? '—' : (string) $level->name,
            'name' => (string) $class->name,
            'code' => $class->code === null ? null : (string) $class->code,
            'display_order' => (int) $class->display_order,
            'is_active' => (bool) $class->is_active,
            'configuration_present' => is_array($configuration) && $configuration !== [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function streamPayload(Stream $stream): array
    {
        $class = $stream->classDefinition;
        $grade = $class?->gradeLevel;

        return [
            'id' => (int) $stream->id,
            'class_definition_id' => (int) $stream->class_definition_id,
            'class_name' => $class === null ? '—' : (string) $class->name,
            'grade_level_name' => $grade === null ? '—' : (string) $grade->name,
            'name' => (string) $stream->name,
            'code' => $stream->code === null ? null : (string) $stream->code,
            'display_order' => (int) $stream->display_order,
            'is_active' => (bool) $stream->is_active,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function offeringPayload(ClassOffering $offering): array
    {
        $class = $offering->classDefinition;
        $grade = $class?->gradeLevel;
        $year = $offering->academicYear;
        $configuration = $offering->configuration;

        return [
            'id' => (int) $offering->id,
            'class_definition_id' => (int) $offering->class_definition_id,
            'class_name' => $class === null ? '—' : (string) $class->name,
            'grade_level_name' => $grade === null ? '—' : (string) $grade->name,
            'academic_year_id' => (int) $offering->academic_year_id,
            'academic_year_name' => $year === null ? '—' : (string) $year->name,
            'name' => $offering->name === null ? null : (string) $offering->name,
            'is_active' => (bool) $offering->is_active,
            'configuration_present' => is_array($configuration) && $configuration !== [],
        ];
    }
}
