<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Domains\Authorization\Services\SchoolPermissions;
use App\Domains\School\Context\SchoolContext;
use App\Frontend\Contracts\CalendarManagementGateway;
use App\Frontend\Contracts\FrontendDataProvider;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Academic calendar administration')]
final class CalendarAdministration extends Component
{
    public int $selectedYearId = 0;

    public string $yearName = '';

    public string $yearStartDate = '';

    public string $yearEndDate = '';

    public string $yearStatus = 'draft';

    public string $yearSource = '';

    public string $yearSourceReference = '';

    public string $yearSourcePublishedAt = '';

    public ?int $editingYearId = null;

    public string $periodName = '';

    public string $periodType = 'term';

    public string $periodSequence = '1';

    public string $periodStartDate = '';

    public string $periodEndDate = '';

    public string $periodStatus = 'draft';

    public string $periodCountingBasis = '';

    public string $periodSource = '';

    public string $periodSourceReference = '';

    public string $periodSourcePublishedAt = '';

    public ?int $editingPeriodId = null;

    /** @var array<string, string> */
    public array $yearErrors = [];

    /** @var array<string, string> */
    public array $periodErrors = [];

    public string $formError = '';

    public string $successMessage = '';

    /** @var array{valid: bool, issues: array<int, string>}|null */
    public ?array $validation = null;

    public function render(CalendarManagementGateway $gateway, FrontendDataProvider $data): View
    {
        // Livewire updates do not replay the originating route gate. Re-check
        // read permission so a revoked assignment cannot keep returning data
        // through an already-open management page.
        $canView = $this->canViewCalendar();
        $years = $canView ? $gateway->years() : [];
        $selectedYear = $years === [] ? null : $this->resolveSelectedYear($years);

        return view('livewire.calendar-administration', [
            'user' => $data->user(),
            'school' => $data->school(),
            'academicYear' => $canView ? $data->currentYear() : null,
            'years' => $years,
            'selectedYear' => $selectedYear,
            'periods' => $selectedYear === null ? [] : $gateway->periods((int) $selectedYear['id']),
            'yearStatusOptions' => $this->options('year_statuses'),
            'periodStatusOptions' => $this->options('period_statuses'),
            'periodTypeOptions' => $this->options('period_types'),
            'countingBasisOptions' => $this->options('counting_bases'),
        ]);
    }

    public function selectYear(int $yearId): void
    {
        $this->selectedYearId = $yearId;
        $this->resetYearForm();
        $this->resetPeriodForm();
        $this->clearMessages();
    }

    public function newYear(): void
    {
        $this->resetYearForm();
        $this->clearMessages();
    }

    public function editYear(int $yearId): void
    {
        if (! $this->canViewCalendar()) {
            $this->clearMessages();
            $this->formError = 'You do not hold permission to view the academic calendar.';

            return;
        }

        foreach ($this->gateway()->years() as $year) {
            if ((int) $year['id'] !== $yearId) {
                continue;
            }

            $this->selectedYearId = $yearId;
            $this->editingYearId = $yearId;
            $this->yearName = (string) ($year['name'] ?? '');
            $this->yearStartDate = (string) ($year['start_date'] ?? '');
            $this->yearEndDate = (string) ($year['end_date'] ?? '');
            $this->yearStatus = (string) ($year['status'] ?? 'draft');
            $this->yearSource = (string) ($year['source'] ?? '');
            $this->yearSourceReference = (string) ($year['source_reference'] ?? '');
            $this->yearSourcePublishedAt = (string) ($year['source_published_at'] ?? '');
            $this->yearErrors = [];
            $this->clearMessages();

            return;
        }

        $this->formError = 'That academic year is not available in this school context.';
    }

    public function saveYear(): void
    {
        if (! $this->canManageCalendar()) {
            $this->clearMessages();
            $this->formError = 'You do not hold permission to manage the academic calendar.';

            return;
        }

        $wasEditing = $this->editingYearId !== null;
        $this->yearErrors = [];
        $this->clearMessages();

        try {
            $year = $wasEditing
                ? $this->gateway()->updateYear((int) $this->editingYearId, $this->yearData())
                : $this->gateway()->createYear($this->yearData());
        } catch (ValidationException $exception) {
            $this->yearErrors = $this->validationErrors($exception);
            $this->formError = 'Check the highlighted fields and try again.';

            return;
        } catch (AuthorizationException) {
            $this->formError = 'This academic year cannot be changed in the current school context.';

            return;
        }

        $this->selectedYearId = (int) $year['id'];
        $this->resetYearForm();
        $this->resetPeriodForm();
        $this->successMessage = $wasEditing ? 'Academic year updated.' : 'Academic year added.';
    }

    public function newPeriod(): void
    {
        $this->resetPeriodForm();
        $this->clearMessages();
    }

    public function editPeriod(int $periodId): void
    {
        if (! $this->canViewCalendar()) {
            $this->clearMessages();
            $this->formError = 'You do not hold permission to view the academic calendar.';

            return;
        }

        foreach ($this->gateway()->periods($this->selectedYearId) as $period) {
            if ((int) $period['id'] !== $periodId) {
                continue;
            }

            $this->editingPeriodId = $periodId;
            $this->periodName = (string) ($period['name'] ?? '');
            $this->periodType = (string) ($period['type'] ?? 'term');
            $this->periodSequence = (string) ($period['sequence'] ?? '1');
            $this->periodStartDate = (string) ($period['start_date'] ?? '');
            $this->periodEndDate = (string) ($period['end_date'] ?? '');
            $this->periodStatus = (string) ($period['status'] ?? 'draft');
            $this->periodCountingBasis = (string) ($period['counting_basis'] ?? '');
            $this->periodSource = (string) ($period['source'] ?? '');
            $this->periodSourceReference = (string) ($period['source_reference'] ?? '');
            $this->periodSourcePublishedAt = (string) ($period['source_published_at'] ?? '');
            $this->periodErrors = [];
            $this->clearMessages();

            return;
        }

        $this->formError = 'That period is not available in the selected academic year.';
    }

    public function savePeriod(): void
    {
        if (! $this->canManageCalendar()) {
            $this->clearMessages();
            $this->formError = 'You do not hold permission to manage the academic calendar.';

            return;
        }

        if ($this->selectedYearId === 0) {
            $this->formError = 'Select an academic year before adding a period.';

            return;
        }

        $wasEditing = $this->editingPeriodId !== null;
        $this->periodErrors = [];
        $this->clearMessages();

        try {
            $period = $wasEditing
                ? $this->gateway()->updatePeriod((int) $this->editingPeriodId, $this->periodData())
                : $this->gateway()->createPeriod($this->selectedYearId, $this->periodData());
        } catch (ValidationException $exception) {
            $this->periodErrors = $this->validationErrors($exception);
            $this->formError = 'Check the highlighted fields and try again.';

            return;
        } catch (AuthorizationException) {
            $this->formError = 'This period cannot be changed in the current school context.';

            return;
        }

        $this->resetPeriodForm();
        $this->successMessage = $wasEditing ? 'Period updated.' : 'Period added.';
    }

    public function validateSelectedYear(): void
    {
        if (! $this->canViewCalendar()) {
            $this->clearMessages();
            $this->formError = 'You do not hold permission to view the academic calendar.';

            return;
        }

        if ($this->selectedYearId === 0) {
            $this->formError = 'Select an academic year before running the check.';

            return;
        }

        $this->clearMessages();

        try {
            $this->validation = $this->gateway()->validateYear($this->selectedYearId);
        } catch (AuthorizationException) {
            $this->formError = 'This academic year cannot be checked in the current school context.';
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $years
     * @return array<string, mixed>|null
     */
    private function resolveSelectedYear(array $years): ?array
    {
        foreach ($years as $year) {
            if ((int) $year['id'] === $this->selectedYearId) {
                return $year;
            }
        }

        $first = $years[0] ?? null;
        $this->selectedYearId = $first === null ? 0 : (int) $first['id'];

        return $first;
    }

    private function resetYearForm(): void
    {
        $this->editingYearId = null;
        $this->yearName = '';
        $this->yearStartDate = '';
        $this->yearEndDate = '';
        $this->yearStatus = 'draft';
        $this->yearSource = '';
        $this->yearSourceReference = '';
        $this->yearSourcePublishedAt = '';
        $this->yearErrors = [];
    }

    private function resetPeriodForm(): void
    {
        $this->editingPeriodId = null;
        $this->periodName = '';
        $this->periodType = 'term';
        $this->periodSequence = '1';
        $this->periodStartDate = '';
        $this->periodEndDate = '';
        $this->periodStatus = 'draft';
        $this->periodCountingBasis = '';
        $this->periodSource = '';
        $this->periodSourceReference = '';
        $this->periodSourcePublishedAt = '';
        $this->periodErrors = [];
    }

    private function clearMessages(): void
    {
        $this->formError = '';
        $this->successMessage = '';
        $this->validation = null;
    }

    /**
     * @return array<string, mixed>
     */
    private function yearData(): array
    {
        return [
            'name' => trim($this->yearName),
            'start_date' => trim($this->yearStartDate),
            'end_date' => trim($this->yearEndDate),
            'status' => $this->yearStatus !== '' ? $this->yearStatus : null,
            'source' => trim($this->yearSource),
            'source_reference' => $this->nullable($this->yearSourceReference),
            'source_published_at' => $this->nullable($this->yearSourcePublishedAt),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function periodData(): array
    {
        return [
            'name' => trim($this->periodName),
            'type' => $this->periodType,
            'sequence' => (int) $this->periodSequence,
            'start_date' => trim($this->periodStartDate),
            'end_date' => trim($this->periodEndDate),
            'status' => $this->periodStatus !== '' ? $this->periodStatus : null,
            'counting_basis' => $this->nullable($this->periodCountingBasis),
            'source' => trim($this->periodSource),
            'source_reference' => $this->nullable($this->periodSourceReference),
            'source_published_at' => $this->nullable($this->periodSourcePublishedAt),
        ];
    }

    private function nullable(string $value): ?string
    {
        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private function options(string $configKey): array
    {
        $options = [];

        foreach ((array) config('academic-calendar.'.$configKey, []) as $value => $details) {
            $label = is_array($details) && isset($details['label'])
                ? (string) $details['label']
                : (string) $value;

            $options[] = [
                'value' => (string) $value,
                'label' => $label,
            ];
        }

        return $options;
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

    /**
     * Livewire updates do not replay the originating page route, so read and
     * write methods must re-check the permissions their routes require.
     */
    private function canViewCalendar(): bool
    {
        return $this->hasCalendarPermission('academic_calendar.view');
    }

    private function canManageCalendar(): bool
    {
        return $this->hasCalendarPermission('academic_calendar.manage');
    }

    private function hasCalendarPermission(string $permission): bool
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return false;
        }

        $school = app(SchoolContext::class)->current();

        if ($school === null) {
            return false;
        }

        return app(SchoolPermissions::class)->has($user, $school, $permission);
    }

    private function gateway(): CalendarManagementGateway
    {
        /** @var CalendarManagementGateway $gateway */
        $gateway = app(CalendarManagementGateway::class);

        return $gateway;
    }
}
