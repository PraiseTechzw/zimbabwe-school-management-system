<?php

declare(strict_types=1);

use App\Domains\AcademicCalendar\Models\AcademicYear;
use App\Domains\AcademicStructure\Models\ClassDefinition;
use App\Domains\AcademicStructure\Models\EducationalLevel;
use App\Domains\AcademicStructure\Models\ClassOffering;
use App\Domains\AcademicStructure\Models\GradeLevel;
use App\Domains\AcademicStructure\Models\Stream;
use App\Domains\Authorization\Models\RoleAssignment;
use App\Domains\School\Context\SchoolContext;
use App\Domains\School\Models\School;
use App\Frontend\Contracts\AcademicStructureGateway;
use App\Frontend\DomainAcademicStructureGateway;
use App\Livewire\AcademicStructure;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(fn (): mixed => $this->withoutVite());

/**
 * @return array<string, mixed>
 */
function structureUiLevelData(array $overrides = []): array
{
    return array_merge([
        'name' => 'Primary',
        'code' => 'PRI',
        'display_order' => 1,
        'is_active' => true,
    ], $overrides);
}

function structureUiUser(School $school, ?string $role = 'school_member'): User
{
    $user = User::factory()->create();
    $user->schools()->attach($school);

    if ($role !== null) {
        RoleAssignment::query()->forceCreate([
            'school_id' => (int) $school->id,
            'user_id' => (int) $user->id,
            'role' => $role,
            'source' => 'domain',
            'created_at' => Carbon::now(),
        ]);
    }

    app(SchoolContext::class)->set($school);
    Auth::login($user);

    return $user;
}

function structureUiLevel(School $school, array $attributes = []): EducationalLevel
{
    return EducationalLevel::factory()->for($school)->create(structureUiLevelData($attributes));
}

function structureUiGrade(School $school, EducationalLevel $level, array $attributes = []): GradeLevel
{
    return GradeLevel::factory()->inLevel($level)->create(array_merge([
        'name' => 'Grade 1',
        'code' => 'G1',
        'display_order' => 1,
        'is_active' => true,
    ], $attributes));
}

function structureUiClass(School $school, GradeLevel $grade, array $attributes = []): ClassDefinition
{
    return ClassDefinition::factory()->inGrade($grade)->create(array_merge([
        'name' => 'Form 1A',
        'code' => 'F1A',
        'display_order' => 1,
        'is_active' => true,
    ], $attributes));
}

it('redirects guests from academic structure to sign-in', function (): void {
    $this->get('/app/academic-structure')->assertRedirect('/login');
});

it('exposes a dedicated navigation link and requires the view permission', function (): void {
    $school = School::factory()->create(['name' => 'Riverside High']);
    $user = structureUiUser($school);

    $this->actingAs($user)
        ->get('/app')
        ->assertOk()
        ->assertSee('Academic structure')
        ->assertSee('href="'.route('frontend.academic-structure').'"', false);

    $this->actingAs($user)
        ->get('/app/academic-structure')
        ->assertOk()
        ->assertSee('Riverside High')
        ->assertSee('Educational levels')
        ->assertSee('No educational levels')
        ->assertDontSee('Save level');
});

it('denies the screen to an authenticated user without the view permission', function (): void {
    $school = School::factory()->create();
    $user = structureUiUser($school, null);

    $this->actingAs($user)->get('/app/academic-structure')->assertForbidden();
});

it('keeps the screen read-only for users without the manage permission', function (): void {
    $school = School::factory()->create();
    structureUiUser($school, 'school_member');

    Livewire::test(AcademicStructure::class)
        ->assertSee('Read-only access')
        ->assertDontSee('Save level')
        ->assertDontSee('>Edit<', false);
});

it('binds the frontend gateway to the domain adapter', function (): void {
    expect(app(AcademicStructureGateway::class))->toBeInstanceOf(DomainAcademicStructureGateway::class);
});

it('refuses writes in the gateway without the manage permission', function (): void {
    $school = School::factory()->create();
    structureUiUser($school, 'school_member');

    expect(fn (): array => app(AcademicStructureGateway::class)->createLevel([
        'name' => 'Unauthorised level',
        'display_order' => 1,
    ]))->toThrow(AuthorizationException::class);

    $this->assertDatabaseMissing('educational_levels', ['name' => 'Unauthorised level']);
});

it('shows empty states in every section for a new school', function (): void {
    $school = School::factory()->create();
    structureUiUser($school, 'school_editor');

    Livewire::test(AcademicStructure::class)
        ->assertSee('No educational levels')
        ->call('selectSection', 'grades')->assertSee('No grade levels')
        ->call('selectSection', 'classes')->assertSee('No classes')
        ->call('selectSection', 'streams')->assertSee('No streams')
        ->call('selectSection', 'offerings')->assertSee('No class offerings');
});

it('creates an educational level with the server-resolved school', function (): void {
    $school = School::factory()->create();
    structureUiUser($school, 'school_editor');

    Livewire::test(AcademicStructure::class)
        ->set('levelName', 'Foundation')
        ->set('levelCode', 'FDN')
        ->set('levelOrder', '1')
        ->call('saveLevel')
        ->assertSet('successMessage', 'Educational level added.');

    $this->assertDatabaseHas('educational_levels', [
        'school_id' => $school->id,
        'name' => 'Foundation',
        'code' => 'FDN',
        'display_order' => 1,
        'is_active' => true,
    ]);
});

it('creates a grade level under an owned educational level', function (): void {
    $school = School::factory()->create();
    $level = structureUiLevel($school, ['name' => 'Secondary', 'display_order' => 2]);
    structureUiUser($school, 'school_editor');

    Livewire::test(AcademicStructure::class)
        ->set('gradeLevelId', (string) $level->id)
        ->set('gradeName', 'Form 1')
        ->set('gradeCode', 'F1')
        ->set('gradeOrder', '2')
        ->call('saveGrade')
        ->assertSet('successMessage', 'Grade level added.');

    $this->assertDatabaseHas('grade_levels', [
        'school_id' => $school->id,
        'educational_level_id' => $level->id,
        'name' => 'Form 1',
        'code' => 'F1',
        'display_order' => 2,
    ]);
});

it('creates a class definition in the selected grade', function (): void {
    $school = School::factory()->create();
    $level = structureUiLevel($school, ['name' => 'Secondary']);
    $grade = structureUiGrade($school, $level);
    structureUiUser($school, 'school_editor');

    Livewire::test(AcademicStructure::class)
        ->set('classGradeId', (string) $grade->id)
        ->set('className', 'Form 1A')
        ->set('classCode', 'F1A')
        ->set('classOrder', '1')
        ->call('saveClass')
        ->assertSet('successMessage', 'Class added.');

    $this->assertDatabaseHas('class_definitions', [
        'school_id' => $school->id,
        'grade_level_id' => $grade->id,
        'name' => 'Form 1A',
        'code' => 'F1A',
    ]);
});

it('creates a stream in the selected class', function (): void {
    $school = School::factory()->create();
    $level = structureUiLevel($school);
    $grade = structureUiGrade($school, $level);
    $class = structureUiClass($school, $grade);
    structureUiUser($school, 'school_editor');

    Livewire::test(AcademicStructure::class)
        ->set('streamClassId', (string) $class->id)
        ->set('streamName', 'Blue stream')
        ->set('streamCode', 'BLU')
        ->set('streamOrder', '1')
        ->call('saveStream')
        ->assertSet('successMessage', 'Stream added.');

    $this->assertDatabaseHas('streams', [
        'school_id' => $school->id,
        'class_definition_id' => $class->id,
        'name' => 'Blue stream',
        'code' => 'BLU',
    ]);
});

it('creates a class offering for an owned class and year', function (): void {
    $school = School::factory()->create();
    $level = structureUiLevel($school);
    $grade = structureUiGrade($school, $level);
    $class = structureUiClass($school, $grade);
    $year = AcademicYear::factory()->for($school)->create(['name' => '2027']);
    structureUiUser($school, 'school_editor');

    Livewire::test(AcademicStructure::class)
        ->set('offeringClassId', (string) $class->id)
        ->set('offeringYearId', (string) $year->id)
        ->call('saveOffering')
        ->assertSet('successMessage', 'Class offering added.');

    $this->assertDatabaseHas('class_offerings', [
        'school_id' => $school->id,
        'class_definition_id' => $class->id,
        'academic_year_id' => $year->id,
        'name' => null,
        'is_active' => true,
    ]);
});

it('updates an educational level through the management form', function (): void {
    $school = School::factory()->create();
    $level = structureUiLevel($school);
    structureUiUser($school, 'school_editor');

    Livewire::test(AcademicStructure::class)
        ->call('editLevel', $level->id)
        ->set('levelName', 'Primary revised')
        ->call('saveLevel')
        ->assertSet('successMessage', 'Educational level updated.');

    $this->assertDatabaseHas('educational_levels', [
        'id' => $level->id,
        'school_id' => $school->id,
        'name' => 'Primary revised',
    ]);
});

it('updates a grade level through the management form', function (): void {
    $school = School::factory()->create();
    $level = structureUiLevel($school);
    $grade = structureUiGrade($school, $level);
    structureUiUser($school, 'school_editor');

    Livewire::test(AcademicStructure::class)
        ->call('editGrade', $grade->id)
        ->set('gradeName', 'Grade 1 revised')
        ->call('saveGrade')
        ->assertSet('successMessage', 'Grade level updated.');

    $this->assertDatabaseHas('grade_levels', [
        'id' => $grade->id,
        'school_id' => $school->id,
        'name' => 'Grade 1 revised',
    ]);
});

it('updates a class definition through the management form', function (): void {
    $school = School::factory()->create();
    $level = structureUiLevel($school);
    $grade = structureUiGrade($school, $level);
    $class = structureUiClass($school, $grade);
    structureUiUser($school, 'school_editor');

    Livewire::test(AcademicStructure::class)
        ->call('editClass', $class->id)
        ->set('className', 'Form 1B')
        ->call('saveClass')
        ->assertSet('successMessage', 'Class updated.');

    $this->assertDatabaseHas('class_definitions', [
        'id' => $class->id,
        'school_id' => $school->id,
        'name' => 'Form 1B',
    ]);
});

it('updates a stream through the management form', function (): void {
    $school = School::factory()->create();
    $level = structureUiLevel($school);
    $grade = structureUiGrade($school, $level);
    $class = structureUiClass($school, $grade);
    $stream = Stream::factory()->inClass($class)->create([
        'name' => 'Green stream',
        'display_order' => 1,
    ]);
    structureUiUser($school, 'school_editor');

    Livewire::test(AcademicStructure::class)
        ->call('editStream', $stream->id)
        ->set('streamName', 'Green stream revised')
        ->call('saveStream')
        ->assertSet('successMessage', 'Stream updated.');

    $this->assertDatabaseHas('streams', [
        'id' => $stream->id,
        'school_id' => $school->id,
        'name' => 'Green stream revised',
    ]);
});

it('updates a class offering through the management form', function (): void {
    $school = School::factory()->create();
    $level = structureUiLevel($school);
    $grade = structureUiGrade($school, $level);
    $class = structureUiClass($school, $grade);
    $year = AcademicYear::factory()->for($school)->create(['name' => '2027']);
    $offering = ClassOffering::factory()
        ->forClassAndYear($class, $year)
        ->create(['name' => 'Morning offering']);
    structureUiUser($school, 'school_editor');

    Livewire::test(AcademicStructure::class)
        ->call('editOffering', $offering->id)
        ->set('offeringName', 'Revised offering')
        ->call('saveOffering')
        ->assertSet('successMessage', 'Class offering updated.');

    $this->assertDatabaseHas('class_offerings', [
        'id' => $offering->id,
        'school_id' => $school->id,
        'name' => 'Revised offering',
    ]);
});

it('shows validation errors without saving an invalid level', function (): void {
    $school = School::factory()->create();
    structureUiUser($school, 'school_editor');

    Livewire::test(AcademicStructure::class)
        ->set('levelName', '')
        ->set('levelOrder', '1')
        ->call('saveLevel')
        ->assertSet('formError', 'Check the highlighted fields and try again.')
        ->assertSet('levelErrors.name', fn (mixed $message): bool => is_string($message) && $message !== '');

    $this->assertDatabaseMissing('educational_levels', ['school_id' => $school->id, 'name' => '']);
});

it('surfaces backend duplicate validation through the form', function (): void {
    $school = School::factory()->create();
    structureUiLevel($school, ['name' => 'Primary', 'display_order' => 1]);
    structureUiUser($school, 'school_editor');

    Livewire::test(AcademicStructure::class)
        ->set('levelName', 'Another primary')
        ->set('levelOrder', '1')
        ->call('saveLevel')
        ->assertSet('levelErrors.display_order', fn (mixed $message): bool => is_string($message) && $message !== '');

    $this->assertDatabaseMissing('educational_levels', ['school_id' => $school->id, 'name' => 'Another primary']);
});

it('rejects a parent from another school without saving the child', function (): void {
    $school = School::factory()->create();
    $other = School::factory()->create();
    $foreignLevel = structureUiLevel($other, ['name' => 'Foreign level']);
    structureUiUser($school, 'school_editor');

    Livewire::test(AcademicStructure::class)
        ->set('gradeLevelId', (string) $foreignLevel->id)
        ->set('gradeName', 'Foreign grade')
        ->set('gradeCode', 'FG')
        ->set('gradeOrder', '1')
        ->call('saveGrade')
        ->assertSet('formError', 'That grade level or its parent is no longer available in this school context.');

    $this->assertDatabaseMissing('grade_levels', ['name' => 'Foreign grade']);
});

it('shows only records from the authorised school', function (): void {
    $school = School::factory()->create(['name' => 'Riverside High']);
    $other = School::factory()->create(['name' => 'Other Academy']);
    $level = structureUiLevel($school, ['name' => 'Local level', 'display_order' => 1]);
    $foreignLevel = structureUiLevel($other, ['name' => 'Foreign level', 'display_order' => 1]);
    structureUiGrade($school, $level, ['name' => 'Local grade']);
    structureUiGrade($other, $foreignLevel, ['name' => 'Foreign grade']);
    $user = structureUiUser($school, 'school_editor');

    $this->actingAs($user)
        ->get('/app/academic-structure')
        ->assertOk()
        ->assertSee('Local level')
        ->assertDontSee('Foreign level')
        ->assertDontSee('Foreign grade');

    structureUiUser($school, 'school_editor');

    Livewire::test(AcademicStructure::class)
        ->assertSee('Local level')
        ->assertDontSee('Foreign level')
        ->call('selectSection', 'grades')
        ->assertSee('Local grade')
        ->assertDontSee('Foreign grade');
});

it('cannot update a foreign record through the gateway', function (): void {
    $school = School::factory()->create();
    $other = School::factory()->create();
    $foreignLevel = structureUiLevel($other, ['name' => 'Foreign level']);
    structureUiUser($school, 'school_editor');

    expect(fn (): array => app(AcademicStructureGateway::class)->updateLevel($foreignLevel->id, [
        'name' => 'Changed elsewhere',
    ]))->toThrow(ModelNotFoundException::class);

    $this->assertDatabaseHas('educational_levels', [
        'id' => $foreignLevel->id,
        'school_id' => $other->id,
        'name' => 'Foreign level',
    ]);
});

it('rejects a client supplied school identifier', function (): void {
    $school = School::factory()->create();
    $other = School::factory()->create();
    structureUiUser($school, 'school_editor');

    expect(fn (): array => app(AcademicStructureGateway::class)->createLevel([
        'school_id' => $other->id,
        'name' => 'Client tenancy attempt',
        'display_order' => 1,
    ]))->toThrow(ValidationException::class);

    $this->assertDatabaseMissing('educational_levels', ['name' => 'Client tenancy attempt']);
});

it('does not expose delete or archive actions', function (): void {
    $school = School::factory()->create();
    structureUiUser($school, 'school_editor');

    $this->actingAs(Auth::user())
        ->get('/app/academic-structure')
        ->assertOk()
        ->assertDontSee('Delete')
        ->assertDontSee('Archive')
        ->assertDontSee('wire:click="delete', false);
});

it('passes the mobile menu trigger into the navigation handler', function (): void {
    $school = School::factory()->create();
    $user = structureUiUser($school);

    $this->actingAs($user)
        ->get('/app/academic-structure')
        ->assertOk()
        ->assertSee('openMenu($event)', false);
});
