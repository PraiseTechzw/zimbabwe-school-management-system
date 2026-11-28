<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domains\School\Context\SchoolContext;
use App\Domains\SchoolSettings\Actions\ListSchoolSettingRevisions;
use App\Domains\SchoolSettings\Actions\ResolveSchoolSettings;
use App\Domains\SchoolSettings\Actions\UpdateSchoolSettings;
use App\Domains\SchoolSettings\Models\SchoolSetting;
use App\Http\Requests\SchoolSettings\UpdateSchoolSettingsRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

final class SchoolSettingsController extends Controller
{
    public function show(Request $request, SchoolContext $context): JsonResponse
    {
        $this->rejectClientSchoolId($request);

        $school = $context->assertCurrent();
        $this->authorizeSchoolPermission($school, 'school_settings.view');
        $settings = app(ResolveSchoolSettings::class)->execute($school);

        return $this->tenantJson($this->settingsPayload($settings));
    }

    public function update(UpdateSchoolSettingsRequest $request, SchoolContext $context): JsonResponse
    {
        $school = $context->assertCurrent();
        $this->authorizeSchoolPermission($school, 'school_settings.manage');

        $settings = app(UpdateSchoolSettings::class)->execute(
            $school,
            $request->validated()
        );

        return $this->tenantJson($this->settingsPayload($settings));
    }

    public function revisions(Request $request, SchoolContext $context): JsonResponse
    {
        $this->rejectClientSchoolId($request);

        $school = $context->assertCurrent();
        $this->authorizeSchoolPermission($school, 'school_settings.view');

        /** @var array<int, array<string, mixed>> $revisions */
        $revisions = app(ListSchoolSettingRevisions::class)->execute($school);

        return $this->tenantJson([
            'school_id' => (int) $school->id,
            'revisions' => $revisions,
        ]);
    }

    private function rejectClientSchoolId(Request $request): void
    {
        Validator::make($request->all(), [
            'school_id' => ['missing'],
        ])->validate();
    }

    /**
     * @return array<string, mixed>
     */
    private function settingsPayload(SchoolSetting $setting): array
    {
        $source = $setting->getRawOriginal('source');
        $effectiveFrom = $setting->getRawOriginal('effective_from');

        return [
            'school_id' => (int) $setting->school_id,
            'settings' => $setting->values(),
            'revision' => [
                'version' => (int) $setting->version,
                'source' => $source === null ? null : (string) $source,
                'effective_from' => $effectiveFrom === null ? null : (string) $effectiveFrom,
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function tenantJson(array $payload, int $status = Response::HTTP_OK): JsonResponse
    {
        return response()->json($payload, $status)
            ->header('Cache-Control', 'no-store')
            ->header('Vary', 'X-School-Id, school_id');
    }
}
