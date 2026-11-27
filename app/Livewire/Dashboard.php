<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Frontend\Contracts\FrontendDataProvider;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Config;
use Livewire\Component;

final class Dashboard extends Component
{
    public function render(FrontendDataProvider $data): View
    {
        $identifiers = $data->identifiers();
        $verified = count(array_filter(
            $identifiers,
            static fn (array $identifier): bool => $identifier['verification_status'] === 'verified',
        ));

        /** @var array<string, list<array{key: string, label: string, available?: bool, route?: string}>> $groups */
        $groups = Config::get('frontend.navigation', []);
        $ready = [];

        foreach ($groups as $items) {
            foreach ($items as $item) {
                if (($item['available'] ?? false) === true) {
                    $ready[] = [
                        'label' => $item['label'],
                        'key' => $item['key'],
                        'route' => $item['route'] ?? null,
                    ];
                }
            }
        }

        return view('livewire.dashboard', [
            'user' => $data->user(),
            'school' => $data->school(),
            'currentYear' => $data->currentYear(),
            'attention' => $data->attention(),
            'identifierCount' => count($identifiers),
            'verifiedCount' => $verified,
            'readyModules' => $ready,
        ]);
    }
}
