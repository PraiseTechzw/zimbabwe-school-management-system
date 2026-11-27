<?php

declare(strict_types=1);

namespace App\Domains\AcademicStructure\Models;

use App\Domains\AcademicCalendar\Models\AcademicYear;
use App\Domains\School\Models\School;
use App\Domains\School\Scopes\ScopesToCurrentSchool;
use Database\Factories\ClassOfferingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An academic-year-specific offering of a structural class definition. This is
 * still structural data: it contains no learners, enrolments or progression.
 */
/**
 * @property int $id
 * @property int $school_id
 * @property int $class_definition_id
 * @property int $academic_year_id
 * @property string|null $name
 * @property bool $is_active
 * @property array<string, mixed>|null $configuration
 */
#[Fillable([
    'name', 'is_active', 'configuration',
])]
class ClassOffering extends Model
{
    /** @use HasFactory<ClassOfferingFactory> */
    use HasFactory, ScopesToCurrentSchool;

    protected static function newFactory(): Factory
    {
        return ClassOfferingFactory::new();
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'configuration' => 'array',
        ];
    }

    /**
     * @return BelongsTo<School, $this>
     */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /**
     * @return BelongsTo<ClassDefinition, $this>
     */
    public function classDefinition(): BelongsTo
    {
        return $this->belongsTo(ClassDefinition::class);
    }

    /**
     * @return BelongsTo<AcademicYear, $this>
     */
    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }
}
