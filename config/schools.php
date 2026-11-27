<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | School classification
    |--------------------------------------------------------------------------
    |
    | Only the statutory Government / non-Government distinction is treated as
    | evidence-backed. Additional categories must be added deliberately with a
    | source rather than inferred from a single school's documents.
    |
    */

    'school_types' => [
        'government' => ['label' => 'Government school'],
        'non-government' => ['label' => 'Non-Government school'],
    ],

    /*
    |--------------------------------------------------------------------------
    | School identifiers
    |--------------------------------------------------------------------------
    |
    | Identifier syntax is unverified. Patterns are null by design; a source-
    | backed pattern can be configured without changing domain code. Values are
    | stored as supplied after trimming, while uniqueness uses a normalized
    | lower-case key so MySQL and SQLite behave consistently.
    |
    */

    'identifier_types' => [
        'emis' => ['label' => 'EMIS code', 'pattern' => null],
        'registration_number' => ['label' => 'Registration number', 'pattern' => null],
    ],

    'verification_statuses' => [
        'proposed' => ['label' => 'Proposed'],
        'verified' => ['label' => 'Verified'],
        'rejected' => ['label' => 'Rejected'],
    ],

    'statuses' => [
        'registered' => ['label' => 'Registered'],
        'provisional' => ['label' => 'Provisional'],
        'satellite' => ['label' => 'Satellite'],
        'unregistered' => ['label' => 'Unregistered'],
    ],

    'relationship_types' => [
        'satellite_of' => ['label' => 'Satellite of'],
    ],

];
