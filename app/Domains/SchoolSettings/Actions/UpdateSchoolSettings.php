<?php

declare(strict_types=1);

namespace App\Domains\SchoolSettings\Actions;

use App\Domains\School\Models\School;
use App\Domains\SchoolSettings\Concerns\GuardsSchoolSettingsIntegrity;
use App\Domains\SchoolSettings\Concerns\RecordsSchoolSettingsAudit;
use App\Domains\SchoolSettings\Models\SchoolSetting;
use App\Domains\SchoolSettings\Models\SchoolSettingRevision;
use Illuminate\Support\Facades\DB;

final class UpdateSchoolSettings
{
    use GuardsSchoolSettingsIntegrity;
    use RecordsSchoolSettingsAudit;

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(School $school, array $data): SchoolSetting
    {
        /** @var array<string, mixed> $validated */
        $validated = app(ValidateSchoolSettings::class)->execute($data);
        $values = $this->normaliseSettings($validated);

        return DB::transaction(function () use ($school, $values): SchoolSetting {
            $lockedSchool = $this->lockSettingsForSchool($school);

            /** @var SchoolSetting|null $setting */
            $setting = SchoolSetting::query()->forSchool($lockedSchool)->lockForUpdate()->first();
            $isFirstSave = $setting === null;
            $setting ??= SchoolSetting::defaultFor($lockedSchool);

            $previous = $isFirstSave ? null : $setting->snapshot();

            $setting->fill($values);
            $setting->version = $isFirstSave ? 1 : (int) $setting->version + 1;
            $setting->save();

            if ($previous !== null) {
                SchoolSettingRevision::query()->create([
                    'school_id' => (int) $lockedSchool->id,
                    'school_setting_id' => (int) $setting->id,
                    'version' => (int) $previous['version'],
                    'values' => (array) $previous['values'],
                    'source' => (string) $previous['source'],
                    'effective_from' => (string) $previous['effective_from'],
                ]);
            }

            /** @var SchoolSetting $fresh */
            $fresh = $setting->fresh();

            $this->recordSchoolSettingsAudit(
                $lockedSchool,
                $isFirstSave ? 'school_settings.created' : 'school_settings.updated',
                $fresh,
                $previous,
            );

            return $fresh;
        });
    }
}
