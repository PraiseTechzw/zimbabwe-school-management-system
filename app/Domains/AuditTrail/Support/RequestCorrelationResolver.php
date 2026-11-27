<?php

declare(strict_types=1);

namespace App\Domains\AuditTrail\Support;

use App\Domains\AuditTrail\Contracts\CorrelationResolver;

/**
 * Reads an allow-listed correlation header only when an HTTP request exists.
 */
final class RequestCorrelationResolver implements CorrelationResolver
{
    public function resolve(): ?string
    {
        $request = request();

        /** @var array<int, string> $headers */
        $headers = (array) config('audit.correlation_headers', []);
        $maximum = (int) config('audit.limits.correlation_length', 100);

        foreach ($headers as $header) {
            $value = $request->headers->get($header);

            if (! is_string($value)) {
                continue;
            }

            $value = trim($value);

            if ($value === '' || strlen($value) > $maximum || preg_match('/^[A-Za-z0-9._:-]+$/', $value) !== 1) {
                continue;
            }

            return $value;
        }

        return null;
    }
}
