<?php

declare(strict_types=1);

namespace App\Http\Controllers\Frontend;

use App\Frontend\Contracts\FrontendDataProvider;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Config;

final class ModuleNoticeController extends Controller
{
    public function __construct(private readonly FrontendDataProvider $data) {}

    public function show(string $module): View
    {
        $label = $this->labelFor($module);

        return view('frontend.module-notice', [
            'user' => $this->data->user(),
            'module' => $module,
            'label' => $label,
        ]);
    }

    private function labelFor(string $key): string
    {
        /** @var array<string, list<array{key: string, label: string}>> $groups */
        $groups = Config::get('frontend.navigation', []);

        foreach ($groups as $items) {
            foreach ($items as $item) {
                if ($item['key'] === $key) {
                    return (string) $item['label'];
                }
            }
        }

        return 'This module';
    }
}
