<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domains\School\Context\SchoolContext;
use App\Domains\School\Models\SchoolIdentifier;
use Illuminate\Http\JsonResponse;

final class SchoolContextController extends Controller
{
    public function current(SchoolContext $context): JsonResponse
    {
        $school = $context->assertCurrent();
        $this->authorizeSchoolPermission($school, 'school.view');

        return $this->tenantJson([
            'school' => [
                'id' => (int) $school->id,
                'name' => $school->name,
                'type' => $school->type,
            ],
        ]);
    }

    public function identifiers(SchoolContext $context): JsonResponse
    {
        $school = $context->assertCurrent();
        $this->authorizeSchoolPermission($school, 'school_identifiers.view');

        $identifiers = SchoolIdentifier::query()
            ->forCurrentSchool()
            ->orderBy('id')
            ->get(['id', 'school_id', 'type', 'value', 'verification_status'])
            ->map(static fn (SchoolIdentifier $identifier): array => [
                'id' => (int) $identifier->id,
                'school_id' => (int) $identifier->school_id,
                'type' => $identifier->type,
                'value' => $identifier->value,
                'verification_status' => $identifier->verification_status,
            ])
            ->all();

        return $this->tenantJson([
            'school_id' => (int) $school->id,
            'identifiers' => $identifiers,
        ]);
    }

    /**
     * These responses depend on the request-selected school, so shared caches
     * must not reuse them for another tenant.
     *
     * @param  array<string, mixed>  $payload
     */
    private function tenantJson(array $payload): JsonResponse
    {
        return response()->json($payload)
            ->header('Cache-Control', 'no-store')
            ->header('Vary', 'X-School-Id');
    }
}
