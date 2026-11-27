<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Permission catalogue
    |--------------------------------------------------------------------------
    |
    | Permissions are named action boundaries, not workflow code. Domain
    | modules opt in by applying the permission middleware to a route or by
    | calling SchoolPermissions directly from a server-side action.
    |
    */

    'permissions' => [
        'school.view' => ['label' => 'View the authorised school context'],
        'school_identifiers.view' => ['label' => 'View school identifiers'],
        'academic_calendar.view' => ['label' => 'View academic calendars'],
        'academic_calendar.manage' => ['label' => 'Create and update academic calendars'],
        'roles.view' => ['label' => 'View role assignments'],
        'roles.manage' => ['label' => 'Assign and remove school roles'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Role catalogue
    |--------------------------------------------------------------------------
    |
    | The database stores only role assignments. Role contents remain in
    | configuration so future roles can be added without schema changes or
    | hardcoded workflow checks. The bootstrap admin role uses '*' to receive
    | future permissions automatically and should be assigned sparingly.
    |
    */

    'roles' => [
        'school_member' => [
            'label' => 'School member',
            'permissions' => [
                'school.view',
                'academic_calendar.view',
            ],
        ],
        'school_editor' => [
            'label' => 'School calendar editor',
            'permissions' => [
                'school.view',
                'academic_calendar.view',
                'academic_calendar.manage',
            ],
        ],
        'role_viewer' => [
            'label' => 'Role viewer',
            'permissions' => [
                'school.view',
                'roles.view',
            ],
        ],
        'school_admin' => [
            'label' => 'School administrator',
            'permissions' => ['*'],
        ],
    ],

    'assignment_sources' => [
        'domain' => ['label' => 'Domain action'],
        'cli' => ['label' => 'Operator command'],
        'import' => ['label' => 'Controlled import'],
    ],

];
