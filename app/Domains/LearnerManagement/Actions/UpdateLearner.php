<?php

declare(strict_types=1);

namespace App\Domains\LearnerManagement\Actions;

use App\Domains\LearnerManagement\Concerns\GuardsLearnerRecords;
use App\Domains\LearnerManagement\Concerns\RecordsLearnerAudit;
use App\Domains\LearnerManagement\Models\Learner;
use App\Domains\LearnerManagement\Support\LearnerRules;
use App\Domains\School\Models\School;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

final class UpdateLearner
{
    use GuardsLearnerRecords;
    use RecordsLearnerAudit;

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(School $school, Learner $learner, array $data): Learner
    {
        $validated = Validator::make(array_merge([
            'full_name' => $learner->full_name,
            'date_of_birth' => $learner->date_of_birth,
            'class_offering_id' => $learner->class_offering_id,
        ], $data), [
            'full_name' => LearnerRules::fullName(),
            'date_of_birth' => LearnerRules::dateOfBirth(),
            'class_offering_id' => LearnerRules::classOffering(),
        ])->validate();

        $name = trim((string) $validated['full_name']);
        $dateOfBirth = isset($validated['date_of_birth']) ? (string) $validated['date_of_birth'] : null;
        $offeringId = isset($validated['class_offering_id']) ? (int) $validated['class_offering_id'] : null;

        return DB::transaction(function () use ($school, $learner, $name, $dateOfBirth, $offeringId): Learner {
            $this->lockLearnerSchool($school);
            $this->assertOwnedBySchool($school, $learner);

            if ($offeringId !== null) {
                $this->resolvePlacement($school, $offeringId);
            }

            $before = $this->learnerAuditSnapshot($learner);

            $learner->fill([
                'full_name' => $name,
                'date_of_birth' => $dateOfBirth,
                'class_offering_id' => $offeringId,
            ]);
            $learner->save();

            $this->recordLearnerAudit(
                $school,
                'learner.updated',
                $learner,
                $before,
                $this->learnerAuditSnapshot($learner),
            );

            return $learner;
        });
    }
}
