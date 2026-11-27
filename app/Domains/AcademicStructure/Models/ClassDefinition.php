<?php

declare(strict_types=1);

namespace App\Domains\AcademicStructure\Models;

use App\Domains\School\Models\School;
use App\Domains\School\Scopes\ScopesToCurrentSchool;
use Database\Factories\ClassDefinitionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A structural class/group definition. It holds no learners and no enrolment.
 */
/**
 * @property int $id
 * @property int $school_id
 * @property int $grade_level_id
 * @property string $name
 * @property string|null $code
 * @property int $display_order
 * @property bool $is_active
 * @property array<string, mixed>|null $configuration
 */
#[Fillable([
    'name', 'code', 'display_order', 'is_active', 'configuration',
])]
class ClassDefinition extends Model
{
    /** @use HasFactory<ClassDefinitionFactory> */
    use HasFactory, ScopesToCurrentSchool;

    protected static function newFactory(): Factory
    {
        return ClassDefinitionFactory::new();
    }

    protected function casts(): array
    {
        return [
            'display_order' => 'integer',
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
     * @return BelongsTo<GradeLevel, $this>
     */
    public function gradeLevel(): BelongsTo
    {
        return $this->belongsTo(GradeLevel::class);
    }

    /**
     * @return HasMany<Stream, $this>
     */
    public function streams(): HasMany
    {
        return $this->hasMany(Stream::class)->orderBy('display_order')->orderBy('id');
    }

    /**
     * @return HasMany<ClassOffering, $this>
     */
    public function offerings(): HasMany
    {
        return $this->hasMany(ClassOffering::class)->orderBy('id');
    }
}
