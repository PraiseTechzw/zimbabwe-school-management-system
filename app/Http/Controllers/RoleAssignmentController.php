<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domains\Authorization\Actions\AssignSchoolRole;
use App\Domains\Authorization\Actions\ListSchoolRoleAssignments;
use App\Domains\Authorization\Actions\RemoveSchoolRole;
use App\Domains\School\Context\SchoolContext;
use App\Http\Requests\Authorization\StoreRoleAssignmentRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

final class RoleAssignmentController extends Controller
{
    public function index(Request $request, SchoolContext $context): JsonResponse
    {
        $this->rejectClientSchoolId($request);

        $actor = $this->actor($request);
        $school = $context->assertCurrent();

        /** @var array<int, array<string, mixed>> $assignments */
        $assignments = app(ListSchoolRoleAssignments::class)->execute($actor, $school);

        return $this->tenantJson([
            'school_id' => (int) $school->id,
            'role_assignments' => $assignments,
        ]);
    }

    public function store(StoreRoleAssignmentRequest $request, SchoolContext $context): JsonResponse
    {
        $actor = $this->actor($request);
        $validated = $request->validated();

        $assignment = app(AssignSchoolRole::class)->execute(
            $actor,
            $context->assertCurrent(),
            (int) $validated['user_id'],
            (string) $validated['role'],
        );

        return $this->tenantJson([
            'role_assignment' => [
                'id' => (int) $assignment->id,
                'school_id' => (int) $assignment->school_id,
                'user_id' => (int) $assignment->user_id,
                'role' => (string) $assignment->role,
                'source' => (string) $assignment->source,
                'created_at' => $assignment->created_at->toIso8601String(),
            ],
        ], Response::HTTP_CREATED);
    }

    public function destroy(Request $request, SchoolContext $context, int $roleAssignment): JsonResponse
    {
        $this->rejectClientSchoolId($request);

        app(RemoveSchoolRole::class)->execute(
            $this->actor($request),
            $context->assertCurrent(),
            $roleAssignment,
        );

        return response()->json(null, Response::HTTP_NO_CONTENT)
            ->header('Cache-Control', 'no-store')
            ->header('Vary', 'X-School-Id, school_id');
    }

    private function actor(Request $request): User
    {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(Response::HTTP_UNAUTHORIZED, 'Authentication is required.');
        }

        return $user;
    }

    private function rejectClientSchoolId(Request $request): void
    {
        Validator::make($request->all(), [
            'school_id' => ['prohibited'],
        ])->validate();
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
