<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Actor and source vocabulary
    |--------------------------------------------------------------------------
    |
    | These are audit classification values, not permissions. Domain modules
    | must still pass a server-resolved School model or an explicit system
    | scope; no client-supplied school identifier is accepted here.
    |
    */

    'actor_types' => [
        'user' => ['label' => 'Authenticated user'],
        'system' => ['label' => 'System'],
        'job' => ['label' => 'Background job'],
    ],

    'sources' => [
        'domain' => ['label' => 'Domain action'],
        'http' => ['label' => 'HTTP request'],
        'cli' => ['label' => 'Command line'],
        'job' => ['label' => 'Background job'],
        'import' => ['label' => 'Import'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Correlation identifiers
    |--------------------------------------------------------------------------
    |
    | A correlation value is used only to join related audit events. It is
    | allow-listed and length-limited so arbitrary request header content
    | cannot become a hidden logging channel.
    |
    */

    'correlation_headers' => [
        'X-Correlation-Id',
        'X-Request-Id',
    ],

    /*
    |--------------------------------------------------------------------------
    | Sensitive key patterns
    |--------------------------------------------------------------------------
    |
    | Audit writes use explicit metadata, but a caller can still pass an
    | unsafe field name. Matching keys are redacted before persistence. This
    | is a deliberately conservative floor, not an exhaustive privacy policy.
    |
    */

    'sensitive_key_patterns' => [
        '/(?:^|_)(?:password|passwd|passphrase|secret|token|credential|credentials|api[_-]?key|authorization|cookie|session|csrf|pin|otp|cvv|cvc)(?:$|_)/i',
        '/(?:^|_)(?:card|card_number|payment_card|bank_account)(?:$|_)/i',
        '/(?:^|_)(?:medical|diagnosis|safeguard|safeguarding|incident|document|attachment|file_contents|request_body|raw_body|full_request)(?:$|_)/i',
    ],

    'limits' => [
        'metadata_keys' => 30,
        'list_items' => 25,
        'depth' => 3,
        'string_length' => 255,
        'correlation_length' => 100,
    ],

];
