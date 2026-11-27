<?php

declare(strict_types=1);

namespace App\Domains\AcademicStructure\Models;

use App\Domains\School\Models\School;
use App\Domains\School\Scopes\ScopesToCurrentSchool;
use Database\Factories\StreamFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An optional subdivision of a structural class definition. A school may
 * define none; the module assigns no meaning such as subject pathway versus
 * parallel group because the documented evidence conflicts (STR-21).
 */
/**
 * @property int $id
 * @property int $school_id
 * @property int $class_definition_id
 * @property string $name
 * @property string|null $code
 * @property int $display_order
 * @property bool $is_active
 */
#[Fillable([
    'name', 'code', 'display_order', 'is_active',
])]
class Stream extends Model
{
    /** @use HasFactory<StreamFactory> */
    use HasFactory, ScopesToCurrentSchool;

    protected static function newFactory(): Factory
    {
        return StreamFactory::new();
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
     * @return BelongsTo<ClassDefinition, $this>
     */
    public function classDefinition(): BelongsTo
    {
        return $this->belongsTo(ClassDefinition::class);
    }
}
