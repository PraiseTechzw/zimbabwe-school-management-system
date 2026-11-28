<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domains\LearnerManagement\Actions\RegisterLearner;
use App\Domains\LearnerManagement\Actions\UpdateLearner;
use App\Domains\LearnerManagement\Models\Learner;
use App\Domains\School\Context\SchoolContext;
use App\Http\Requests\LearnerManagement\StoreLearnerRequest;
use App\Http\Requests\LearnerManagement\UpdateLearnerRequest;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

final class LearnerController extends Controller
{
    public function index(Request $request, SchoolContext $context): JsonResponse
    {
        $validated = Validator::make($request->all(), [
            'school_id' => ['prohibited'],
            'search' => ['sometimes', 'nullable', 'string', 'max:120'],
            'per_page' => [
                'sometimes',
                'integer',
                'min:1',
                'max:'.(int) config('learner-management.max_per_page', 100),
            ],
            'page' => ['sometimes', 'integer', 'min:1'],
        ])->validate();

        $search = isset($validated['search']) ? trim((string) $validated['search']) : null;
        $search = $search === '' ? null : $search;
        $perPage = (int) ($validated['per_page'] ?? config('learner-management.default_per_page', 25));

        $query = Learner::query()->forCurrentSchool();

        if ($search !== null) {
            $escaped = addcslashes(Str::lower($search), '\\%_');

            $query->where(function ($nested) use ($escaped): void {
                $nested->whereRaw("LOWER(full_name) LIKE ? ESCAPE '\\'", ['%'.$escaped.'%'])
                    ->orWhereRaw("LOWER(learner_reference) LIKE ? ESCAPE '\\'", ['%'.$escaped.'%']);
            });
        }

        $paginator = $query->orderByDesc('id')
            ->paginate($perPage)
            ->through(fn (Learner $learner): array => $this->learnerPayload($learner));

        return $this->tenantJson([
            'school_id' => (int) $context->assertCurrent()->id,
            'search' => $search,
            'learners' => $paginator->toArray(),
        ]);
    }

    public function store(StoreLearnerRequest $request, SchoolContext $context): JsonResponse
    {
        $learner = app(RegisterLearner::class)->execute($context->assertCurrent(), $request->validated());

        return $this->tenantJson(['learner' => $this->learnerPayload($learner)], Response::HTTP_CREATED);
    }

    public function show(SchoolContext $context, int $learner): JsonResponse
    {
        return $this->tenantJson(['learner' => $this->learnerPayload($this->scopedLearner($learner))]);
    }

    public function update(UpdateLearnerRequest $request, SchoolContext $context, int $learner): JsonResponse
    {
        $record = $this->scopedLearner($learner);
        $updated = app(UpdateLearner::class)->execute($context->assertCurrent(), $record, $request->validated());

        return $this->tenantJson(['learner' => $this->learnerPayload($updated)]);
    }

    private function scopedLearner(int $id): Learner
    {
        try {
            /** @var Learner $learner */
            $learner = Learner::query()->forCurrentSchool()->findOrFail($id);

            return $learner;
        } catch (ModelNotFoundException) {
            // Missing and foreign records must be indistinguishable.
            abort(Response::HTTP_NOT_FOUND, 'The learner was not found.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function learnerPayload(Learner $learner): array
    {
        return [
            'id' => (int) $learner->id,
            'school_id' => (int) $learner->school_id,
            'learner_reference' => (string) $learner->learner_reference,
            'full_name' => (string) $learner->full_name,
            'date_of_birth' => $learner->date_of_birth === null ? null : (string) $learner->date_of_birth,
            'status' => (string) $learner->status,
            'class_offering_id' => $learner->class_offering_id === null ? null : (int) $learner->class_offering_id,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function tenantJson(array $payload, int $status = Response::HTTP_OK): JsonResponse
    {
        return response()->json($payload, $status)
            ->header('Cache-Control', 'no-store')
            ->header('Vary', 'X-School-Id, school_id');
    }
}
