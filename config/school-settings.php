<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Supported school settings
    |--------------------------------------------------------------------------
    |
    | Issue #38 requires a school-scoped, typed and validated settings
    | mechanism but does not define a catalogue. U-13 remains unresolved, so
    | this slice deliberately exposes only framework-supported regional
    | preferences. Currency, terminology, attendance, finance and module
    | settings are not added without evidence or a Product Owner decision.
    |
    */

    'locales' => ['en'],

    'defaults' => [
        'locale' => 'en',
        'timezone' => 'UTC',
    ],
];
