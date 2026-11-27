<?php

declare(strict_types=1);

namespace App\Domains\SchoolSettings\Actions;

use App\Domains\School\Models\School;
use App\Domains\SchoolSettings\Models\SchoolSettingRevision;

final class ListSchoolSettingRevisions
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function execute(School $school): array
    {
        /** @var array<int, array<string, mixed>> $revisions */
        $revisions = SchoolSettingRevision::query()
            ->forSchool($school)
            ->orderByDesc('version')
            ->orderByDesc('id')
            ->get()
            ->map(static fn (SchoolSettingRevision $revision): array => $revision->payload())
            ->all();

        return $revisions;
    }
}
