<?php

declare(strict_types=1);

namespace App\Domains\SchoolSettings\Models;

use App\Domains\School\Models\School;
use App\Domains\School\Scopes\ScopesToCurrentSchool;
use Database\Factories\SchoolSettingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One settings record per school.
 *
 * Only explicitly allow-listed fields are fillable. Client payloads never
 * reach the model directly: FormRequests filter them and domain actions set
 * the tenant identifier from the resolved School.
 */
#[Fillable(['locale', 'timezone', 'source', 'effective_from'])]
class SchoolSetting extends Model
{
    /** @use HasFactory<SchoolSettingFactory> */
    use HasFactory, ScopesToCurrentSchool;

    protected static function newFactory(): Factory
    {
        return SchoolSettingFactory::new();
    }

    protected function casts(): array
    {
        return [
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
     * @return HasMany<SchoolSettingRevision, $this>
     */
    public function revisions(): HasMany
    {
        return $this->hasMany(SchoolSettingRevision::class)->orderByDesc('version')->orderByDesc('id');
    }

    /**
     * Returns an unsaved record carrying the configured defaults.
     *
     * Reads use this so a GET request never creates tenant data.
     */
    public static function defaultFor(School $school): self
    {
        /** @var array<string, mixed> $defaults */
        $defaults = config('school-settings.defaults', []);

        $setting = new self;
        $setting->fill([
            'locale' => (string) ($defaults['locale'] ?? 'en'),
            'timezone' => (string) ($defaults['timezone'] ?? 'UTC'),
        ]);
        $setting->school_id = $school->id;
        $setting->version = 1;

        return $setting;
    }

    /**
     * @return array<string, string>
     */
    public function values(): array
    {
        return [
            'locale' => (string) $this->locale,
            'timezone' => (string) $this->timezone,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function snapshot(): array
    {
        return [
            'version' => (int) $this->version,
            'values' => $this->values(),
            'source' => (string) $this->source,
            'effective_from' => (string) $this->getRawOriginal('effective_from'),
        ];
    }
}
