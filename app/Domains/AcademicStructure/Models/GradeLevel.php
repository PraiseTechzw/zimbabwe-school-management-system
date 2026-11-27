<?php

declare(strict_types=1);

namespace App\Domains\AcademicStructure\Models;

use App\Domains\School\Models\School;
use App\Domains\School\Scopes\ScopesToCurrentSchool;
use Database\Factories\GradeLevelFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $school_id
 * @property int $educational_level_id
 * @property string $name
 * @property string|null $code
 * @property int $display_order
 * @property bool $is_active
 */
#[Fillable([
    'name', 'code', 'display_order', 'is_active',
])]
class GradeLevel extends Model
{
    /** @use HasFactory<GradeLevelFactory> */
    use HasFactory, ScopesToCurrentSchool;

    protected static function newFactory(): Factory
    {
        return GradeLevelFactory::new();
    }

    protected function casts(): array
    {
        return [
            'display_order' => 'integer',
            'is_active' => 'boolean',
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
     * @return BelongsTo<EducationalLevel, $this>
     */
    public function educationalLevel(): BelongsTo
    {
        return $this->belongsTo(EducationalLevel::class);
    }

    /**
     * @return HasMany<ClassDefinition, $this>
     */
    public function classes(): HasMany
    {
        return $this->hasMany(ClassDefinition::class)->orderBy('display_order')->orderBy('id');
    }
}
