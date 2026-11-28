<?php

declare(strict_types=1);

namespace App\Http\Requests\LearnerManagement;

use App\Domains\LearnerManagement\Support\LearnerRules;
use Illuminate\Foundation\Http\FormRequest;

final class StoreLearnerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'id' => ['prohibited'],
            'school_id' => ['prohibited'],
            'learner_reference' => ['prohibited'],
            'status' => ['prohibited'],
            'created_at' => ['prohibited'],
            'updated_at' => ['prohibited'],
            'full_name' => LearnerRules::fullName(),
            'date_of_birth' => LearnerRules::dateOfBirth(),
            'class_offering_id' => LearnerRules::classOffering(),
        ];
    }
}
