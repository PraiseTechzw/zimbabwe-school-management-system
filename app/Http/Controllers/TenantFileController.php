<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

/**
 * Serves and stores private files only through tenant-bound signed URLs.
 *
 * Authorization is performed by EnsureTenantSignedStorage. This controller
 * deliberately accepts no client-supplied school identifier.
 */
final class TenantFileController extends Controller
{
    public function show(string $path): Response
    {
        abort_unless(Storage::disk('local')->exists($path), Response::HTTP_NOT_FOUND);

        return Storage::disk('local')->response($path, null, [
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Content-Security-Policy' => "default-src 'none'; style-src 'unsafe-inline'; sandbox",
        ]);
    }

    public function store(Request $request, string $path): Response
    {
        Storage::disk('local')->put($path, $request->getContent(true));

        return response()->noContent();
    }
}
