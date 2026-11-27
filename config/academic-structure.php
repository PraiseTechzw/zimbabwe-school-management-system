<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | No prescribed structure
    |--------------------------------------------------------------------------
    |
    | ECD A/B, Grades 1-7, Forms 1-4 and Lower/Upper Sixth are documented
    | nomenclature in STR-05 (see docs/research/findings/02). They are examples
    | for configuration only. This module ships no seeded levels, grades,
    | classes or streams and makes no claim about a universal national model.
    |
    */

    'max_display_order' => 10000,

    'default_is_active' => true,

    /*
    |--------------------------------------------------------------------------
    | Provisional engineering rules
    |--------------------------------------------------------------------------
    |
    | These flags are safeguards for structural integrity, not evidence-backed
    | requirements. They are configurable so a verified policy or Product Owner
    | decision can change them without a code change. Deactivating a parent is
    | intentionally allowed: inactive records remain available for historical
    | structures, while these flags govern creation of new associations.
    |
    */

    'require_active_parent' => true,

    'allow_inactive_class_offering' => false,

];
