<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Domains\Auth\Actions\AttemptLogin;
use App\Domains\Auth\Resources\AuthenticatedUserResource;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

final class LoginController extends Controller
{
    public function store(LoginRequest $request, AttemptLogin $login): JsonResponse
    {
        $validated = $request->validated();

        $user = $login->handle(
            email: (string) $validated['email'],
            password: (string) $validated['password'],
        );

        if ($user === null) {
            throw ValidationException::withMessages([
                'email' => 'The provided credentials are incorrect.',
            ]);
        }

        Auth::guard('web')->login($user);
        $request->session()->regenerate();

        return (new AuthenticatedUserResource($user))
            ->response()
            ->setStatusCode(JsonResponse::HTTP_OK);
    }
}
