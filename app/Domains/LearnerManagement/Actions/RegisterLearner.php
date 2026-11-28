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
use Illuminate\Support\Str;

final class RegisterLearner
{
    use GuardsLearnerRecords;
    use RecordsLearnerAudit;

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(School $school, array $data): Learner
    {
        $validated = Validator::make($data, [
            'full_name' => LearnerRules::fullName(),
            'date_of_birth' => LearnerRules::dateOfBirth(),
            'class_offering_id' => LearnerRules::classOffering(),
        ])->validate();

        $name = trim((string) $validated['full_name']);
        $dateOfBirth = isset($validated['date_of_birth']) ? (string) $validated['date_of_birth'] : null;
        $offeringId = isset($validated['class_offering_id']) ? (int) $validated['class_offering_id'] : null;

        return DB::transaction(function () use ($school, $name, $dateOfBirth, $offeringId): Learner {
            $this->lockLearnerSchool($school);

            if ($offeringId !== null) {
                $this->resolvePlacement($school, $offeringId);
            }

            $learner = new Learner;
            $learner->fill([
                'full_name' => $name,
                'date_of_birth' => $dateOfBirth,
                'class_offering_id' => $offeringId,
            ]);
            $learner->school_id = $school->id;
            $learner->learner_reference = 'LRN-'.Str::upper((string) Str::ulid());
            $learner->status = 'registered';
            $learner->save();

            $this->recordLearnerAudit(
                $school,
                'learner.registered',
                $learner,
                null,
                $this->learnerAuditSnapshot($learner),
            );

            return $learner;
        });
    }
}
