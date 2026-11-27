<?php

declare(strict_types=1);

namespace App\Http\Requests\AcademicStructure;

use App\Domains\AcademicStructure\Support\AcademicStructureRules;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateEducationalLevelRequest extends FormRequest
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
            'name' => AcademicStructureRules::optional(AcademicStructureRules::name()),
            'code' => AcademicStructureRules::optional(AcademicStructureRules::code()),
            'display_order' => AcademicStructureRules::optional(AcademicStructureRules::displayOrder()),
            'is_active' => AcademicStructureRules::optional(AcademicStructureRules::active()),
            'configuration' => AcademicStructureRules::optional(AcademicStructureRules::configuration()),
        ];
    }
}
