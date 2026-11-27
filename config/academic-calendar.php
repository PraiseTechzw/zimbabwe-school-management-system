<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Lifecycle values
    |--------------------------------------------------------------------------
    |
    | WF-02 proposes review before publication. These are configurable values,
    | not a claim that every school uses the same publication workflow.
    |
    */

    'year_statuses' => [
        'draft' => ['label' => 'Draft'],
        'published' => ['label' => 'Published'],
        'archived' => ['label' => 'Archived'],
    ],

    'period_statuses' => [
        'draft' => ['label' => 'Draft'],
        'published' => ['label' => 'Published'],
        'archived' => ['label' => 'Archived'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Period types and counting basis
    |--------------------------------------------------------------------------
    |
    | "Term" is documented practice (PRAC-09–PRAC-11); the other values are
    | generic options, not an assertion about Zimbabwean school structures.
    | No period count or name is hard-coded. Counting basis exists because
    | source day counts conflict (C-07: school days vs calendar days).
    |
    */

    'period_types' => [
        'term' => ['label' => 'Term'],
        'semester' => ['label' => 'Semester'],
        'break' => ['label' => 'Break'],
        'other' => ['label' => 'Other'],
    ],

    'counting_bases' => [
        'school_days' => ['label' => 'School days'],
        'calendar_days' => ['label' => 'Calendar days'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Provisional integrity rules
    |--------------------------------------------------------------------------
    |
    | These rules are engineering safeguards, not evidence-backed requirements.
    | They are configurable so a school can adapt them when a verified source
    | or a Product Owner decision requires a different calendar structure.
    |
    */

    'require_periods_within_year' => true,
    'prevent_period_overlap' => true,
    'prevent_year_overlap' => false,

];
