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

        return response()->json([
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

        return response()->json([
            'school_id' => (int) $school->id,
            'identifiers' => $identifiers,
        ]);
    }
}
