<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Learner record safeguards
    |--------------------------------------------------------------------------
    |
    | These are PROPOSED engineering safeguards, not sourced school policy.
    | The placement flag protects new learner records from being attached to
    | inactive structure; the pagination bound protects the list endpoint.
    |
    */

    'default_per_page' => 25,

    'max_per_page' => 100,

    'require_active_placement' => true,

];
