<?php

declare(strict_types=1);

namespace App\Http\Requests\AcademicStructure;

use App\Domains\AcademicStructure\Support\AcademicStructureRules;
use Illuminate\Foundation\Http\FormRequest;

final class StoreEducationalLevelRequest extends FormRequest
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
            'school_id' => ['missing'],
            'name' => AcademicStructureRules::name(),
            'code' => AcademicStructureRules::code(),
            'display_order' => AcademicStructureRules::displayOrder(),
            'is_active' => array_merge(['nullable'], AcademicStructureRules::active()),
            'configuration' => AcademicStructureRules::configuration(),
        ];
    }
}
