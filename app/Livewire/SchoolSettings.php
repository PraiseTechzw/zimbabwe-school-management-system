<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Domains\Authorization\Services\SchoolPermissions;
use App\Domains\School\Context\SchoolContext;
use App\Frontend\Contracts\FrontendDataProvider;
use App\Frontend\Contracts\SchoolSettingsGateway;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Title;
use Livewire\Component;
use Throwable;

#[Title('School settings')]
final class SchoolSettings extends Component
{
    public string $locale = '';

    public string $timezone = '';

    public string $source = '';

    public string $effectiveFrom = '';

    public bool $formLoaded = false;

    public bool $canManage = false;

    /** @var array<string, string> */
    public array $errorsByField = [];

    public string $formError = '';

    public string $successMessage = '';

    public function render(SchoolSettingsGateway $gateway, FrontendDataProvider $data): View
    {
        $current = $gateway->current();
        $this->canManage = $this->hasManagePermission();

        if (! $this->formLoaded) {
            $this->applySettings($current);
            $this->formLoaded = true;
        }

        return view('livewire.school-settings', [
            'user' => $data->user(),
            'school' => $data->school(),
            'academicYear' => $data->currentYear(),
            'settings' => $current,
            'revisions' => $gateway->revisions(),
            'localeOptions' => $this->localeOptions(),
            'timezoneOptions' => $this->timezoneOptions(),
            'canManage' => $this->canManage,
        ]);
    }

    private function hasManagePermission(): bool
    {
        $user = Auth::user();

        return $user instanceof User
            && app(SchoolPermissions::class)->has(
                $user,
                app(SchoolContext::class)->assertCurrent(),
                'school_settings.manage',
            );
    }

    public function save(): void
    {
        $this->errorsByField = [];
        $this->formError = '';
        $this->successMessage = '';

        try {
            $saved = $this->gateway()->update($this->data());
        } catch (ValidationException $exception) {
            $this->errorsByField = $this->validationErrors($exception);
            $this->formError = 'Check the highlighted fields and try again.';

            return;
        } catch (AuthorizationException) {
            $this->formError = 'Your account cannot manage school settings in this school.';

            return;
        } catch (Throwable) {
            $this->formError = 'The settings could not be saved in this school context.';

            return;
        }

        $this->applySettings($saved);
        $this->successMessage = 'School settings saved.';
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    private function applySettings(array $settings): void
    {
        $values = is_array($settings['settings'] ?? null) ? $settings['settings'] : [];
        $revision = is_array($settings['revision'] ?? null) ? $settings['revision'] : [];

        $this->locale = (string) ($values['locale'] ?? 'en');
        $this->timezone = (string) ($values['timezone'] ?? 'UTC');
        $this->source = (string) ($revision['source'] ?? '');
        $effectiveFrom = $revision['effective_from'] ?? null;
        $this->effectiveFrom = is_string($effectiveFrom) ? $effectiveFrom : '';
    }

    /**
     * @return array<string, mixed>
     */
    private function data(): array
    {
        return [
            'locale' => trim($this->locale),
            'timezone' => trim($this->timezone),
            'source' => trim($this->source),
            'effective_from' => $this->nullable($this->effectiveFrom),
        ];
    }

    private function nullable(string $value): ?string
    {
        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function localeOptions(): array
    {
        /** @var array<int, string> $locales */
        $locales = array_values((array) config('school-settings.locales', ['en']));

        return array_map(
            static fn (string $locale): array => ['value' => $locale, 'label' => strtoupper($locale)],
            $locales,
        );
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function timezoneOptions(): array
    {
        $options = [];

        foreach (\DateTimeZone::listIdentifiers() as $timezone) {
            $options[] = [
                'value' => (string) $timezone,
                'label' => str_replace('_', ' ', (string) $timezone),
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

    private function gateway(): SchoolSettingsGateway
    {
        /** @var SchoolSettingsGateway $gateway */
        $gateway = app(SchoolSettingsGateway::class);

        return $gateway;
    }
}
