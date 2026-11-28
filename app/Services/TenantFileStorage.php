<?php

declare(strict_types=1);

namespace App\Services;

use App\Domains\School\Context\SchoolContext;
use DateTimeInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\URL;

/**
 * Tenant-bound signed file URLs for the private local disk.
 *
 * Laravel's framework-provided signed storage routes are not authenticated and
 * only bind the signature to a path. This service additionally binds every URL
 * to the school selected by ResolveSchoolContext and to a canonical
 * `schools/{school_id}/...` path prefix.
 */
final readonly class TenantFileStorage
{
    public const DISK = 'local';

    /** Query key that carries the signed tenant scope, distinct from client school selectors. */
    public const TENANT_PARAMETER = 'tenant_id';

    public function __construct(private SchoolContext $context) {}

    /**
     * Create a signed URL for a file inside the current school's directory.
     *
     * @throws AuthorizationException when no school is authorised or the path is invalid
     */
    public function signedUrl(string $path, DateTimeInterface $expiration, bool $upload = false): string
    {
        $school = $this->context->assertCurrent();
        $schoolId = (int) $school->id;

        self::assertTenantPath($schoolId, $path);

        $parameters = [
            'path' => $path,
            self::TENANT_PARAMETER => $schoolId,
        ];

        if ($upload) {
            $parameters['upload'] = '1';
        }

        return URL::temporarySignedRoute(
            $upload ? 'storage.local.upload' : 'storage.local',
            $expiration,
            $parameters,
        );
    }

    /**
     * Fail closed for paths that could escape the school's directory.
     *
     * @throws AuthorizationException
     */
    public static function assertTenantPath(int $schoolId, string $path): void
    {
        $prefix = 'schools/'.$schoolId.'/';

        if (! str_starts_with($path, $prefix)) {
            throw new AuthorizationException('The file path is outside the authorised school.');
        }

        $relative = substr($path, strlen($prefix));

        if ($relative === ''
            || str_contains($path, '\\')
            || str_contains($path, "\0")
            || preg_match('/^[A-Za-z0-9][A-Za-z0-9._\/-]*$/', $relative) !== 1) {
            throw new AuthorizationException('The file path is not a valid school file path.');
        }

        foreach (explode('/', $relative) as $segment) {
            if ($segment === '' || $segment === '.' || $segment === '..') {
                throw new AuthorizationException('The file path is not a valid school file path.');
            }
        }
    }
}
