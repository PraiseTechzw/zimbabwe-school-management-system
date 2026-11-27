<?php

declare(strict_types=1);

namespace App\Domains\SchoolSettings\Models;

use App\Domains\School\Models\School;
use App\Domains\School\Scopes\ScopesToCurrentSchool;
use Database\Factories\SchoolSettingRevisionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Append-oriented snapshot of the settings state before a change.
 *
 * This is provenance for issue #38, not the audit event model from issue #40.
 * Revisions are written inside the same transaction as the update and are
 * never modified by application code.
 */
#[Fillable(['school_id', 'school_setting_id', 'version', 'values', 'source', 'effective_from'])]
class SchoolSettingRevision extends Model
{
    /** @use HasFactory<SchoolSettingRevisionFactory> */
    use HasFactory, ScopesToCurrentSchool;

    protected static function newFactory(): Factory
    {
        return SchoolSettingRevisionFactory::new();
    }

    protected function casts(): array
    {
        return [
            'values' => 'array',
            'effective_from' => 'date:Y-m-d',
            'version' => 'integer',
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
     * @return BelongsTo<SchoolSetting, $this>
     */
    public function setting(): BelongsTo
    {
        return $this->belongsTo(SchoolSetting::class, 'school_setting_id');
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        /** @var array<string, mixed> $values */
        $values = $this->values;

        return [
            'id' => (int) $this->id,
            'version' => (int) $this->version,
            'values' => $values,
            'source' => (string) $this->source,
            'effective_from' => (string) $this->getRawOriginal('effective_from'),
            'created_at' => (string) $this->created_at,
        ];
    }
}
