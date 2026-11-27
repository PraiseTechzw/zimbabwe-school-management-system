<?php

declare(strict_types=1);

namespace App\Http\Requests\SchoolSettings;

use App\Domains\SchoolSettings\Actions\ValidateSchoolSettings;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateSchoolSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return ValidateSchoolSettings::rules();
    }
}
