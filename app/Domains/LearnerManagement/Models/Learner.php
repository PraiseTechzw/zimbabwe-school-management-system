<?php

declare(strict_types=1);

namespace App\Domains\LearnerManagement\Models;

use App\Domains\AcademicStructure\Models\ClassOffering;
use App\Domains\School\Models\School;
use App\Domains\School\Scopes\ScopesToCurrentSchool;
use Database\Factories\LearnerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A school-owned learner identity record.
 *
 * Status is record status only. It carries no enrolment, attendance or
 * progression meaning, and the MVP exposes no transition.
 *
 * @property int $id
 * @property int $school_id
 * @property string $learner_reference
 * @property string $full_name
 * @property string|null $date_of_birth
 * @property string $status
 * @property int|null $class_offering_id
 */
#[Fillable([
    'full_name', 'date_of_birth', 'class_offering_id',
])]
class Learner extends Model
{
    /** @use HasFactory<LearnerFactory> */
    use HasFactory, ScopesToCurrentSchool;

    protected static function newFactory(): Factory
    {
        return LearnerFactory::new();
    }

    /**
     * @return BelongsTo<School, $this>
     */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /**
     * @return BelongsTo<ClassOffering, $this>
     */
    public function classOffering(): BelongsTo
    {
        return $this->belongsTo(ClassOffering::class);
    }
}
