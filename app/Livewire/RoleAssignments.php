<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Frontend\Contracts\FrontendDataProvider;
use App\Frontend\Contracts\RoleAssignmentGateway;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Roles')]
final class RoleAssignments extends Component
{
    public int $userId = 0;

    public string $role = '';

    /** @var array<string, string> */
    public array $errorsByField = [];

    public string $formError = '';

    public string $successMessage = '';

    public function render(RoleAssignmentGateway $gateway, FrontendDataProvider $data): View
    {
        $canView = $gateway->canView();
        $assignments = $canView ? $gateway->assignments() : [];
        $roleLabels = [];

        foreach ($gateway->roles() as $option) {
            $roleLabels[(string) $option['value']] = (string) $option['label'];
        }

        return view('livewire.role-assignments', [
            'user' => $data->user(),
            'school' => $data->school(),
            'academicYear' => $data->currentYear(),
            'assignments' => $assignments,
            'roleOptions' => $gateway->roles(),
            'roleLabels' => $roleLabels,
            'canView' => $canView,
            'canManage' => $gateway->canManage(),
        ]);
    }

    public function assign(): void
    {
        $this->errorsByField = [];
        $this->formError = '';
        $this->successMessage = '';

        if (! $this->gateway()->canManage()) {
            $this->formError = 'Your account cannot manage role assignments in this school.';

            return;
        }

        if ($this->userId === 0 || $this->role === '') {
            $this->formError = 'Choose a school member and a role.';

            return;
        }

        try {
            $this->gateway()->assign($this->userId, $this->role);
        } catch (ValidationException $exception) {
            $this->errorsByField = $this->validationErrors($exception);
            $this->formError = 'Check the highlighted fields and try again.';

            return;
        } catch (AuthorizationException) {
            $this->formError = 'That role cannot be granted in this school context.';

            return;
        } catch (ModelNotFoundException) {
            $this->formError = 'That school member is no longer available.';

            return;
        }

        $this->userId = 0;
        $this->role = '';
        $this->successMessage = 'Role assigned.';
    }

    public function remove(int $assignmentId): void
    {
        $this->errorsByField = [];
        $this->formError = '';
        $this->successMessage = '';

        try {
            $this->gateway()->remove($assignmentId);
        } catch (AuthorizationException) {
            $this->formError = 'Your account cannot remove role assignments in this school.';

            return;
        } catch (ModelNotFoundException) {
            $this->formError = 'That assignment is no longer available in this school.';

            return;
        }

        $this->successMessage = 'Role assignment removed.';
    }

    /**
     * @return array<string, string>
     */
    private function validationErrors(ValidationException $exception): array
    {
        $errors = [];

        foreach ($exception->validator->errors()->messages() as $field => $messages) {
            $errors[(string) $field] = (string) ($messages[0] ?? 'This value is not valid.');
        }

        return $errors;
    }

    private function gateway(): RoleAssignmentGateway
    {
        /** @var RoleAssignmentGateway $gateway */
        $gateway = app(RoleAssignmentGateway::class);

        return $gateway;
    }
}
