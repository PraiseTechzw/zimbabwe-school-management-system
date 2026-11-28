<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Domains\Authorization\Services\SchoolPermissions;
use App\Domains\School\Context\SchoolContext;
use App\Frontend\Contracts\FrontendDataProvider;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Config;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Dashboard')]
final class Dashboard extends Component
{
    public function render(
        FrontendDataProvider $data,
        SchoolPermissions $permissions,
        SchoolContext $context,
    ): View {
        $identifiers = $data->identifiers();
        $verified = count(array_filter(
            $identifiers,
            static fn (array $identifier): bool => $identifier['verification_status'] === 'verified',
        ));

        /** @var array<string, list<array{key: string, label: string, available?: bool, route?: string, permission?: string}>> $groups */
        $groups = Config::get('frontend.navigation', []);
        $actor = auth()->user();
        $permissionSchool = $context->current();
        $canSee = static function (array $item) use ($actor, $permissionSchool, $permissions): bool {
            $permission = $item['permission'] ?? null;

            if ($permission === null) {
                return true;
            }

            return $actor instanceof User
                && $permissionSchool !== null
                && $permissions->has($actor, $permissionSchool, $permission);
        };
        $ready = [];

        foreach ($groups as $items) {
            foreach ($items as $item) {
                if (($item['available'] ?? false) === true && $item['key'] !== 'dashboard' && $canSee($item)) {
                    $ready[] = [
                        'label' => $item['label'],
                        'key' => $item['key'],
                        'route' => $item['route'] ?? null,
                    ];
                }
            }
        }

        $currentYear = $data->currentYear();
        $periods = $currentYear === null ? [] : $data->periods((int) $currentYear['id']);

        /** @var list<array{label: string, value: string, note: string}> $gaps */
        $gaps = [
            ['label' => 'Enrolled learners', 'value' => '—', 'note' => 'No verified endpoint yet'],
            ['label' => 'Teachers and staff', 'value' => '—', 'note' => 'No verified endpoint yet'],
            ['label' => 'Classes', 'value' => '—', 'note' => 'No verified endpoint yet'],
            ['label' => 'Attendance', 'value' => '—', 'note' => 'No verified endpoint yet'],
            ['label' => 'Fee records', 'value' => '—', 'note' => 'No verified endpoint yet'],
            ['label' => 'Outstanding balances', 'value' => '—', 'note' => 'No verified endpoint yet'],
            [
                'label' => 'Calendar events',
                'value' => $currentYear === null ? '—' : (string) count($periods),
                'note' => $currentYear === null ? 'No current academic year' : 'Dated periods in the current year',
            ],
            ['label' => 'Recent activity', 'value' => '—', 'note' => 'No verified endpoint yet'],
        ];

        return view('livewire.dashboard', [
            'user' => $data->user(),
            'school' => $data->school(),
            'currentYear' => $data->currentYear(),
            'attention' => $data->attention(),
            'identifierCount' => count($identifiers),
            'verifiedCount' => $verified,
            'readyModules' => $ready,
            'gaps' => $gaps,
        ]);
    }
}
