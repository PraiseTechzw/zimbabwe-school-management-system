<?php

declare(strict_types=1);

namespace App\Domains\School\Models;

use App\Domains\School\Scopes\ScopesToCurrentSchool;
use Database\Factories\SchoolRelationshipFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['school_id', 'related_school_id', 'relationship_type', 'valid_from', 'valid_to', 'evidence_reference'])]
class SchoolRelationship extends Model
{
    /** @use HasFactory<SchoolRelationshipFactory> */
    use HasFactory, ScopesToCurrentSchool;

    protected static function newFactory(): Factory
    {
        return SchoolRelationshipFactory::new();
    }

    protected function casts(): array
    {
        return [
            'valid_from' => 'date',
            'valid_to' => 'date',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class, 'school_id');
    }

    public function relatedSchool(): BelongsTo
    {
        return $this->belongsTo(School::class, 'related_school_id');
    }
}
