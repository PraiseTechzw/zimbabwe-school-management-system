<?php

declare(strict_types=1);

namespace App\Domains\AcademicStructure\Models;

use App\Domains\School\Models\School;
use App\Domains\School\Scopes\ScopesToCurrentSchool;
use Database\Factories\EducationalLevelFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $school_id
 * @property string $name
 * @property string|null $code
 * @property int $display_order
 * @property bool $is_active
 * @property array<string, mixed>|null $configuration
 */
#[Fillable([
    'name', 'code', 'display_order', 'is_active', 'configuration',
])]
class EducationalLevel extends Model
{
    /** @use HasFactory<EducationalLevelFactory> */
    use HasFactory, ScopesToCurrentSchool;

    protected static function newFactory(): Factory
    {
        return EducationalLevelFactory::new();
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
     * @return HasMany<GradeLevel, $this>
     */
    public function gradeLevels(): HasMany
    {
        return $this->hasMany(GradeLevel::class)->orderBy('display_order')->orderBy('id');
    }
}
