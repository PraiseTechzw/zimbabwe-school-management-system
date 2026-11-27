<?php

declare(strict_types=1);

namespace App\Domains\AcademicCalendar\Models;

use App\Domains\School\Models\School;
use App\Domains\School\Scopes\ScopesToCurrentSchool;
use Database\Factories\AcademicYearFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'school_id', 'name', 'start_date', 'end_date', 'status',
    'source', 'source_reference', 'source_published_at',
])]
class AcademicYear extends Model
{
    /** @use HasFactory<AcademicYearFactory> */
    use HasFactory, ScopesToCurrentSchool;

    protected static function newFactory(): Factory
    {
        return AcademicYearFactory::new();
    }

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'source_published_at' => 'date',
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
     * @return HasMany<AcademicPeriod, $this>
     */
    public function periods(): HasMany
    {
        return $this->hasMany(AcademicPeriod::class)->orderBy('sequence')->orderBy('id');
    }
}
