<?php

declare(strict_types=1);

/*
 * Frontend navigation catalogue.
 *
 * Every destination is declared here so navigation stays configurable per
 * deployment. `available` items have a working screen; planned items lead to
 * an honest "not built yet" screen rather than a dead link. The optional
 * `permission` key mirrors the backend route gate so navigation does not link
 * to a page the actor cannot open; it is not an authorisation boundary. The
 * backend remains responsible for access control.
 */
return [
    /*
     * Demo values are isolated in DemoFrontendDataProvider and labelled in the
     * UI. Never enable this in a deployment that presents data as real.
     */
    'demo' => [
        'enabled' => env('FRONTEND_DEMO', false),
    ],

    /*
     * Deployment-level navigation visibility. This is presentation only:
     * the backend remains the sole authority for access control.
     */
    'hidden_modules' => [],

    'navigation' => [
        'Overview' => [
            ['key' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'dashboard', 'available' => true, 'route' => 'frontend.dashboard', 'permission' => 'school.view'],
        ],
        'School' => [
            ['key' => 'school-profile', 'label' => 'School profile', 'icon' => 'school', 'available' => true, 'route' => 'frontend.school-profile', 'permission' => 'school.view'],
            ['key' => 'academic-calendar', 'label' => 'Academic calendar', 'icon' => 'calendar', 'available' => true, 'route' => 'frontend.academic-calendar', 'permission' => 'academic_calendar.view'],
            ['key' => 'academic-structure', 'label' => 'Academic structure', 'icon' => 'school', 'available' => true, 'route' => 'frontend.academic-structure', 'permission' => 'academic_structure.view'],
            ['key' => 'school-settings', 'label' => 'School settings', 'icon' => 'cog', 'available' => true, 'route' => 'frontend.school-settings', 'permission' => 'school_settings.view'],
        ],
        'Learners' => [
            ['key' => 'admissions', 'label' => 'Admissions', 'icon' => 'users'],
            ['key' => 'learner-directory', 'label' => 'Learner directory', 'icon' => 'users'],
            ['key' => 'guardians', 'label' => 'Guardians', 'icon' => 'users'],
            ['key' => 'enrolment', 'label' => 'Enrolment', 'icon' => 'users'],
            ['key' => 'transfers', 'label' => 'Transfers', 'icon' => 'users'],
            ['key' => 'progression', 'label' => 'Progression', 'icon' => 'users'],
        ],
        'Academics' => [
            ['key' => 'subjects', 'label' => 'Subjects', 'icon' => 'book'],
            ['key' => 'classes', 'label' => 'Classes', 'icon' => 'book'],
            ['key' => 'timetable', 'label' => 'Timetable', 'icon' => 'calendar'],
            ['key' => 'assessment', 'label' => 'Assessment', 'icon' => 'clipboard'],
            ['key' => 'examinations', 'label' => 'Examinations', 'icon' => 'clipboard'],
            ['key' => 'results', 'label' => 'Results', 'icon' => 'clipboard'],
            ['key' => 'report-cards', 'label' => 'Report cards', 'icon' => 'clipboard'],
        ],
        'Attendance' => [
            ['key' => 'learner-attendance', 'label' => 'Learner attendance', 'icon' => 'clipboard'],
            ['key' => 'staff-attendance', 'label' => 'Staff attendance', 'icon' => 'clipboard'],
            ['key' => 'attendance-reports', 'label' => 'Attendance reports', 'icon' => 'clipboard'],
        ],
        'Finance' => [
            ['key' => 'fees', 'label' => 'Fees', 'icon' => 'wallet'],
            ['key' => 'billing', 'label' => 'Billing', 'icon' => 'wallet'],
            ['key' => 'payments', 'label' => 'Payments', 'icon' => 'wallet'],
            ['key' => 'receipts', 'label' => 'Receipts', 'icon' => 'wallet'],
            ['key' => 'statements', 'label' => 'Statements', 'icon' => 'wallet'],
            ['key' => 'arrears', 'label' => 'Arrears', 'icon' => 'wallet'],
        ],
        'Staff' => [
            ['key' => 'staff-directory', 'label' => 'Staff directory', 'icon' => 'users'],
            ['key' => 'leave', 'label' => 'Leave', 'icon' => 'calendar'],
            ['key' => 'payroll', 'label' => 'Payroll', 'icon' => 'wallet'],
        ],
        'Welfare' => [
            ['key' => 'discipline', 'label' => 'Discipline', 'icon' => 'clipboard'],
            ['key' => 'welfare', 'label' => 'Welfare', 'icon' => 'clipboard'],
            ['key' => 'medical-records', 'label' => 'Medical records', 'icon' => 'clipboard'],
        ],
        'Boarding' => [
            ['key' => 'boarding-houses', 'label' => 'Boarding houses', 'icon' => 'school'],
            ['key' => 'room-allocation', 'label' => 'Room allocation', 'icon' => 'school'],
        ],
        'Transport' => [
            ['key' => 'transport-routes', 'label' => 'Routes', 'icon' => 'route'],
            ['key' => 'vehicles', 'label' => 'Vehicles', 'icon' => 'route'],
        ],
        'Operations' => [
            ['key' => 'procurement', 'label' => 'Procurement', 'icon' => 'clipboard'],
            ['key' => 'inventory', 'label' => 'Inventory', 'icon' => 'clipboard'],
            ['key' => 'assets', 'label' => 'Assets', 'icon' => 'clipboard'],
        ],
        'Library' => [
            ['key' => 'library', 'label' => 'Library catalogue', 'icon' => 'book'],
            ['key' => 'borrowing', 'label' => 'Borrowing', 'icon' => 'book'],
        ],
        'Activities' => [
            ['key' => 'sports', 'label' => 'Sports', 'icon' => 'clipboard'],
            ['key' => 'clubs', 'label' => 'Clubs', 'icon' => 'users'],
        ],
        'Communication' => [
            ['key' => 'announcements', 'label' => 'Announcements', 'icon' => 'clipboard'],
            ['key' => 'messages', 'label' => 'Messages', 'icon' => 'clipboard'],
        ],
        'Governance' => [
            ['key' => 'sdc', 'label' => 'SDC', 'icon' => 'school'],
            ['key' => 'meetings', 'label' => 'Meetings', 'icon' => 'calendar'],
            ['key' => 'minutes', 'label' => 'Minutes', 'icon' => 'clipboard'],
        ],
        'System' => [
            ['key' => 'users', 'label' => 'Users', 'icon' => 'users'],
            ['key' => 'roles', 'label' => 'Roles', 'icon' => 'cog', 'available' => true, 'route' => 'frontend.roles', 'permission' => 'roles.view'],
            ['key' => 'audit-logs', 'label' => 'Audit logs', 'icon' => 'clipboard'],
        ],
    ],
];
