<?php

declare(strict_types=1);

namespace App\Domains\School\Models;

use App\Domains\School\Scopes\ScopesToCurrentSchool;
use Database\Factories\SchoolStatusFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['school_id', 'status', 'valid_from', 'valid_to', 'source'])]
class SchoolStatus extends Model
{
    /** @use HasFactory<SchoolStatusFactory> */
    use HasFactory, ScopesToCurrentSchool;

    protected static function newFactory(): Factory
    {
        return SchoolStatusFactory::new();
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
        return $this->belongsTo(School::class);
    }
}
