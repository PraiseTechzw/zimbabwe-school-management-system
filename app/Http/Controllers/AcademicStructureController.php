<?php

declare(strict_types=1);

namespace App\Http\Controllers;

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
use App\Domains\School\Context\SchoolContext;
use App\Http\Requests\AcademicStructure\StoreClassDefinitionRequest;
use App\Http\Requests\AcademicStructure\StoreClassOfferingRequest;
use App\Http\Requests\AcademicStructure\StoreEducationalLevelRequest;
use App\Http\Requests\AcademicStructure\StoreGradeLevelRequest;
use App\Http\Requests\AcademicStructure\StoreStreamRequest;
use App\Http\Requests\AcademicStructure\UpdateClassDefinitionRequest;
use App\Http\Requests\AcademicStructure\UpdateClassOfferingRequest;
use App\Http\Requests\AcademicStructure\UpdateEducationalLevelRequest;
use App\Http\Requests\AcademicStructure\UpdateGradeLevelRequest;
use App\Http\Requests\AcademicStructure\UpdateStreamRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

final class AcademicStructureController extends Controller
{
    public function levels(SchoolContext $context): JsonResponse
    {
        $school = $context->assertCurrent();

        /** @var array<int, array<string, mixed>> $levels */
        $levels = EducationalLevel::query()
            ->forCurrentSchool()
            ->orderBy('display_order')
            ->orderBy('id')
            ->get()
            ->map(fn (EducationalLevel $level): array => $this->levelPayload($level))
            ->all();

        return $this->tenantJson([
            'school_id' => (int) $school->id,
            'educational_levels' => $levels,
        ]);
    }

    public function storeLevel(StoreEducationalLevelRequest $request, SchoolContext $context): JsonResponse
    {
        $level = app(CreateEducationalLevel::class)->execute($context->assertCurrent(), $request->validated());

        return $this->tenantJson(['educational_level' => $this->levelPayload($level)], Response::HTTP_CREATED);
    }

    public function updateLevel(UpdateEducationalLevelRequest $request, SchoolContext $context, int $educationalLevel): JsonResponse
    {
        $level = $this->scopedLevel($educationalLevel);
        $updated = app(UpdateEducationalLevel::class)->execute($context->assertCurrent(), $level, $request->validated());

        return $this->tenantJson(['educational_level' => $this->levelPayload($updated)]);
    }

    public function gradeLevels(SchoolContext $context, int $educationalLevel): JsonResponse
    {
        $level = $this->scopedLevel($educationalLevel);

        /** @var array<int, array<string, mixed>> $grades */
        $grades = $level->gradeLevels()
            ->forCurrentSchool()
            ->get()
            ->map(fn (GradeLevel $grade): array => $this->gradePayload($grade))
            ->all();

        return $this->tenantJson([
            'school_id' => (int) $level->school_id,
            'educational_level_id' => (int) $level->id,
            'grade_levels' => $grades,
        ]);
    }

    public function storeGrade(StoreGradeLevelRequest $request, SchoolContext $context, int $educationalLevel): JsonResponse
    {
        $level = $this->scopedLevel($educationalLevel);
        $data = array_merge($request->validated(), ['educational_level_id' => (int) $level->id]);
        $grade = app(CreateGradeLevel::class)->execute($context->assertCurrent(), $data);

        return $this->tenantJson(['grade_level' => $this->gradePayload($grade)], Response::HTTP_CREATED);
    }

    public function updateGrade(UpdateGradeLevelRequest $request, SchoolContext $context, int $gradeLevel): JsonResponse
    {
        $grade = $this->scopedGrade($gradeLevel);
        $updated = app(UpdateGradeLevel::class)->execute($context->assertCurrent(), $grade, $request->validated());

        return $this->tenantJson(['grade_level' => $this->gradePayload($updated)]);
    }

    public function allGradeLevels(SchoolContext $context): JsonResponse
    {
        /** @var array<int, array<string, mixed>> $grades */
        $grades = GradeLevel::query()
            ->forCurrentSchool()
            ->orderBy('display_order')
            ->orderBy('id')
            ->get()
            ->map(fn (GradeLevel $grade): array => $this->gradePayload($grade))
            ->all();

        return $this->tenantJson([
            'school_id' => (int) $context->assertCurrent()->id,
            'grade_levels' => $grades,
        ]);
    }

    public function classes(SchoolContext $context, int $gradeLevel): JsonResponse
    {
        $grade = $this->scopedGrade($gradeLevel);

        /** @var array<int, array<string, mixed>> $classes */
        $classes = $grade->classes()
            ->forCurrentSchool()
            ->get()
            ->map(fn (ClassDefinition $class): array => $this->classPayload($class))
            ->all();

        return $this->tenantJson([
            'school_id' => (int) $grade->school_id,
            'grade_level_id' => (int) $grade->id,
            'classes' => $classes,
        ]);
    }

    public function storeClass(StoreClassDefinitionRequest $request, SchoolContext $context, int $gradeLevel): JsonResponse
    {
        $grade = $this->scopedGrade($gradeLevel);
        $data = array_merge($request->validated(), ['grade_level_id' => (int) $grade->id]);
        $class = app(CreateClassDefinition::class)->execute($context->assertCurrent(), $data);

        return $this->tenantJson(['class' => $this->classPayload($class)], Response::HTTP_CREATED);
    }

    public function updateClass(UpdateClassDefinitionRequest $request, SchoolContext $context, int $classDefinition): JsonResponse
    {
        $class = $this->scopedClass($classDefinition);
        $updated = app(UpdateClassDefinition::class)->execute($context->assertCurrent(), $class, $request->validated());

        return $this->tenantJson(['class' => $this->classPayload($updated)]);
    }

    public function streams(SchoolContext $context, int $classDefinition): JsonResponse
    {
        $class = $this->scopedClass($classDefinition);

        /** @var array<int, array<string, mixed>> $streams */
        $streams = $class->streams()
            ->forCurrentSchool()
            ->get()
            ->map(fn (Stream $stream): array => $this->streamPayload($stream))
            ->all();

        return $this->tenantJson([
            'school_id' => (int) $class->school_id,
            'class_definition_id' => (int) $class->id,
            'streams' => $streams,
        ]);
    }

    public function storeStream(StoreStreamRequest $request, SchoolContext $context, int $classDefinition): JsonResponse
    {
        $class = $this->scopedClass($classDefinition);
        $data = array_merge($request->validated(), ['class_definition_id' => (int) $class->id]);
        $stream = app(CreateStream::class)->execute($context->assertCurrent(), $data);

        return $this->tenantJson(['stream' => $this->streamPayload($stream)], Response::HTTP_CREATED);
    }

    public function updateStream(UpdateStreamRequest $request, SchoolContext $context, int $stream): JsonResponse
    {
        $record = $this->scopedStream($stream);
        $updated = app(UpdateStream::class)->execute($context->assertCurrent(), $record, $request->validated());

        return $this->tenantJson(['stream' => $this->streamPayload($updated)]);
    }

    public function offerings(Request $request, SchoolContext $context): JsonResponse
    {
        $validated = Validator::make($request->all(), [
            'school_id' => ['prohibited'],
            'academic_year_id' => ['sometimes', 'required', 'integer', 'min:1'],
        ])->validate();

        $query = ClassOffering::query()->forCurrentSchool();

        if (isset($validated['academic_year_id'])) {
            $year = $this->scopedYear((int) $validated['academic_year_id']);
            $query->where('academic_year_id', $year->id);
        }

        /** @var array<int, array<string, mixed>> $offerings */
        $offerings = $query->orderBy('id')
            ->get()
            ->map(fn (ClassOffering $offering): array => $this->offeringPayload($offering))
            ->all();

        return $this->tenantJson([
            'school_id' => (int) $context->assertCurrent()->id,
            'academic_year_id' => isset($validated['academic_year_id']) ? (int) $validated['academic_year_id'] : null,
            'class_offerings' => $offerings,
        ]);
    }

    public function storeOffering(StoreClassOfferingRequest $request, SchoolContext $context): JsonResponse
    {
        $offering = app(CreateClassOffering::class)->execute($context->assertCurrent(), $request->validated());

        return $this->tenantJson(['class_offering' => $this->offeringPayload($offering)], Response::HTTP_CREATED);
    }

    public function updateOffering(UpdateClassOfferingRequest $request, SchoolContext $context, int $classOffering): JsonResponse
    {
        $offering = $this->scopedOffering($classOffering);
        $updated = app(UpdateClassOffering::class)->execute($context->assertCurrent(), $offering, $request->validated());

        return $this->tenantJson(['class_offering' => $this->offeringPayload($updated)]);
    }

    private function scopedLevel(int $id): EducationalLevel
    {
        /** @var EducationalLevel $level */
        $level = EducationalLevel::query()->forCurrentSchool()->findOrFail($id);

        return $level;
    }

    private function scopedGrade(int $id): GradeLevel
    {
        /** @var GradeLevel $grade */
        $grade = GradeLevel::query()->forCurrentSchool()->findOrFail($id);

        return $grade;
    }

    private function scopedClass(int $id): ClassDefinition
    {
        /** @var ClassDefinition $class */
        $class = ClassDefinition::query()->forCurrentSchool()->findOrFail($id);

        return $class;
    }

    private function scopedStream(int $id): Stream
    {
        /** @var Stream $stream */
        $stream = Stream::query()->forCurrentSchool()->findOrFail($id);

        return $stream;
    }

    private function scopedYear(int $id): AcademicYear
    {
        /** @var AcademicYear $year */
        $year = AcademicYear::query()->forCurrentSchool()->findOrFail($id);

        return $year;
    }

    private function scopedOffering(int $id): ClassOffering
    {
        /** @var ClassOffering $offering */
        $offering = ClassOffering::query()->forCurrentSchool()->findOrFail($id);

        return $offering;
    }

    /**
     * @return array<string, mixed>
     */
    private function levelPayload(EducationalLevel $level): array
    {
        return [
            'id' => (int) $level->id,
            'school_id' => (int) $level->school_id,
            'name' => $level->name,
            'code' => $level->code,
            'display_order' => (int) $level->display_order,
            'is_active' => (bool) $level->is_active,
            'configuration' => $level->configuration,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function gradePayload(GradeLevel $grade): array
    {
        return [
            'id' => (int) $grade->id,
            'school_id' => (int) $grade->school_id,
            'educational_level_id' => (int) $grade->educational_level_id,
            'name' => $grade->name,
            'code' => $grade->code,
            'display_order' => (int) $grade->display_order,
            'is_active' => (bool) $grade->is_active,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function classPayload(ClassDefinition $class): array
    {
        return [
            'id' => (int) $class->id,
            'school_id' => (int) $class->school_id,
            'grade_level_id' => (int) $class->grade_level_id,
            'name' => $class->name,
            'code' => $class->code,
            'display_order' => (int) $class->display_order,
            'is_active' => (bool) $class->is_active,
            'configuration' => $class->configuration,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function streamPayload(Stream $stream): array
    {
        return [
            'id' => (int) $stream->id,
            'school_id' => (int) $stream->school_id,
            'class_definition_id' => (int) $stream->class_definition_id,
            'name' => $stream->name,
            'code' => $stream->code,
            'display_order' => (int) $stream->display_order,
            'is_active' => (bool) $stream->is_active,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function offeringPayload(ClassOffering $offering): array
    {
        return [
            'id' => (int) $offering->id,
            'school_id' => (int) $offering->school_id,
            'class_definition_id' => (int) $offering->class_definition_id,
            'academic_year_id' => (int) $offering->academic_year_id,
            'name' => $offering->name,
            'is_active' => (bool) $offering->is_active,
            'configuration' => $offering->configuration,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function tenantJson(array $payload, int $status = Response::HTTP_OK): JsonResponse
    {
        return response()->json($payload, $status)
            ->header('Cache-Control', 'no-store')
            ->header('Vary', 'X-School-Id, school_id');
    }
}
