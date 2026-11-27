<?php

declare(strict_types=1);

namespace App\Domains\AuditTrail\Support;

use BackedEnum;
use DateTimeInterface;
use InvalidArgumentException;

/**
 * Reduces caller-supplied metadata to a small, safe JSON structure.
 *
 * This deliberately rejects objects and whole request bodies. Sensitive key
 * names are redacted recursively rather than relied on at the call site.
 */
final class SanitizeAuditData
{
    private const REDACTED = '[REDACTED]';

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function sanitize(array $data, string $context): array
    {
        $maximum = (int) config('audit.limits.metadata_keys', 30);

        if (count($data) > $maximum) {
            throw new InvalidArgumentException(sprintf('Audit %s may contain at most %d entries.', $context, $maximum));
        }

        if ($context === 'changes') {
            return self::sanitizeChanges($data);
        }

        return self::sanitizeArray($data, 1, $context);
    }

    /**
     * @param  array<mixed>  $data
     * @return array<string, mixed>
     */
    private static function sanitizeChanges(array $data): array
    {
        if (array_diff(array_keys($data), ['before', 'after']) !== []) {
            throw new InvalidArgumentException('Audit changes may contain only before and after keys.');
        }

        /** @var array<string, mixed> $sanitized */
        $sanitized = [];

        foreach ($data as $key => $value) {
            if ($value !== null && ! is_array($value)) {
                throw new InvalidArgumentException(sprintf('Audit change "%s" must be an object-like array or null.', (string) $key));
            }

            $sanitized[(string) $key] = is_array($value) ? self::sanitizeArray($value, 1, (string) $key) : null;
        }

        return $sanitized;
    }

    /**
     * @param  array<mixed>  $data
     * @return array<string, mixed>
     */
    private static function sanitizeArray(array $data, int $depth, string $path): array
    {
        $depthLimit = (int) config('audit.limits.depth', 3);
        $listLimit = (int) config('audit.limits.list_items', 25);

        if ($depth > $depthLimit || count($data) > $listLimit) {
            throw new InvalidArgumentException(sprintf('Audit %s is too large or too deeply nested.', $path));
        }

        $isList = array_is_list($data);

        /** @var array<string, mixed> $sanitized */
        $sanitized = [];

        foreach ($data as $key => $value) {
            $key = (string) $key;
            $childPath = $path.'.'.$key;

            if (! $isList && preg_match('/^[A-Za-z][A-Za-z0-9_-]{0,49}$/', $key) !== 1) {
                throw new InvalidArgumentException(sprintf('Audit key "%s" is not an allowed metadata key.', $key));
            }

            if (self::isSensitiveKey($key)) {
                $sanitized[$key] = self::REDACTED;

                continue;
            }

            $sanitized[$key] = self::sanitizeValue($value, $depth, $childPath);
        }

        return $sanitized;
    }

    private static function sanitizeValue(mixed $value, int $depth, string $path): mixed
    {
        if ($value === null || is_bool($value) || is_int($value) || is_float($value)) {
            return $value;
        }

        if (is_string($value)) {
            $maximum = (int) config('audit.limits.string_length', 255);

            return strlen($value) > $maximum ? substr($value, 0, $maximum).'…[truncated]' : $value;
        }

        if ($value instanceof BackedEnum) {
            return $value->value;
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format(DATE_ATOM);
        }

        if (is_array($value)) {
            return self::sanitizeArray($value, $depth + 1, $path);
        }

        throw new InvalidArgumentException(sprintf('Audit value at %s has an unsupported type.', $path));
    }

    private static function isSensitiveKey(string $key): bool
    {
        /** @var array<int, string> $patterns */
        $patterns = (array) config('audit.sensitive_key_patterns', []);

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $key) === 1) {
                return true;
            }
        }

        return false;
    }
}
