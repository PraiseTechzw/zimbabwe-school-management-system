<?php

declare(strict_types=1);

namespace App\Http\Requests\AcademicCalendar;

use App\Domains\School\Context\SchoolContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateAcademicPeriodRequest extends FormRequest
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
            'academic_year_id' => [
                'sometimes',
                'required',
                'integer',
                Rule::exists('academic_years', 'id')->where('school_id', (int) app(SchoolContext::class)->assertCurrent()->id),
            ],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(array_keys((array) config('academic-calendar.period_types', [])))],
            'sequence' => ['required', 'integer', 'min:1', 'max:10000'],
            'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date_format:Y-m-d', 'after:start_date'],
            'status' => ['nullable', Rule::in(array_keys((array) config('academic-calendar.period_statuses', [])))],
            'counting_basis' => ['nullable', Rule::in(array_keys((array) config('academic-calendar.counting_bases', [])))],
            'source' => ['required', 'string', 'max:255', 'not_regex:/^\s*$/'],
            'source_reference' => ['nullable', 'string', 'max:255'],
            'source_published_at' => ['nullable', 'date_format:Y-m-d'],
        ];
    }
}
