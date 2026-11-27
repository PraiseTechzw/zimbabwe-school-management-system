<?php

declare(strict_types=1);

namespace App\Http\Requests\AcademicStructure;

use App\Domains\AcademicStructure\Support\AcademicStructureRules;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateClassOfferingRequest extends FormRequest
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
            'school_id' => ['prohibited'],
            'class_definition_id' => ['sometimes', 'required', 'integer', 'min:1'],
            'academic_year_id' => ['sometimes', 'required', 'integer', 'min:1'],
            'name' => array_merge(['nullable'], array_slice(AcademicStructureRules::name(), 1)),
            'is_active' => AcademicStructureRules::optional(AcademicStructureRules::active()),
            'configuration' => AcademicStructureRules::optional(AcademicStructureRules::configuration()),
        ];
    }
}
