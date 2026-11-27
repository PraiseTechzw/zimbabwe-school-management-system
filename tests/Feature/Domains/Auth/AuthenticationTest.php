<?php

declare(strict_types=1);

use App\Domains\Auth\Actions\CreateUser;
use App\Domains\School\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Cache::flush();
});

const AUTH_TEST_SECRET = 'SecurePass123!';

function authTestUser(string $email = 'user@example.test', string $password = AUTH_TEST_SECRET): User
{
    return (new CreateUser)->handle([
        'name' => 'Test User',
        'email' => $email,
        'password' => $password,
        'password_confirmation' => $password,
    ]);
}

/**
 * @return array<string, string>
 */
function authCredentials(string $email, string $password): array
{
    return ['email' => $email, 'password' => $password];
}

it('stores passwords with the configured hasher rather than plaintext', function (): void {
    $user = authTestUser();

    expect($user->password)->not->toBe('SecurePass123!')
        ->and(Hash::check('SecurePass123!', $user->password))->toBeTrue()
        ->and(DB::table('users')->where('id', $user->id)->value('password'))->not->toBe('SecurePass123!');
});

it('rejects passwords that do not meet the baseline strength rules', function (): void {
    expect(fn (): User => (new CreateUser)->handle([
        'name' => 'Weak User',
        'email' => 'weak@example.test',
        'password' => 'short',
        'password_confirmation' => 'short',
    ]))->toThrow(ValidationException::class);
});

it('normalises email addresses and rejects duplicates regardless of case', function (): void {
    $user = (new CreateUser)->handle([
        'name' => 'Case User',
        'email' => '  Mixed.Case@Example.TEST ',
        'password' => 'SecurePass123!',
        'password_confirmation' => 'SecurePass123!',
    ]);

    expect($user->email)->toBe('mixed.case@example.test');

    expect(fn (): User => (new CreateUser)->handle([
        'name' => 'Duplicate User',
        'email' => 'MIXED.CASE@example.test',
        'password' => 'SecurePass123!',
        'password_confirmation' => 'SecurePass123!',
    ]))->toThrow(ValidationException::class);
});

it('attaches only validated school memberships during user creation', function (): void {
    $school = School::factory()->create();

    $user = (new CreateUser)->handle([
        'name' => 'School User',
        'email' => 'school-user@example.test',
        'password' => AUTH_TEST_SECRET,
        'password_confirmation' => AUTH_TEST_SECRET,
        'school_ids' => [$school->id],
    ]);

    expect($user->schools()->pluck('schools.id')->map(fn (mixed $id): int => (int) $id)->all())
        ->toBe([(int) $school->id]);
});

it('rejects an unknown school membership during user creation', function (): void {
    expect(fn (): User => (new CreateUser)->handle([
        'name' => 'Bad School User',
        'email' => 'bad-school@example.test',
        'password' => AUTH_TEST_SECRET,
        'password_confirmation' => AUTH_TEST_SECRET,
        'school_ids' => [999999],
    ]))->toThrow(ValidationException::class);
});

it('authenticates a user with valid credentials', function (): void {
    $user = authTestUser();

    $response = $this->postJson('/api/auth/login', authCredentials('USER@EXAMPLE.TEST', 'SecurePass123!'));

    $response->assertOk()
        ->assertJsonPath('user.email', 'user@example.test')
        ->assertJsonPath('user.status', 'active');

    expect($response->json('user.last_login_at'))->not->toBeNull();

    $this->assertAuthenticatedAs($user);
});

it('regenerates the session identifier on login', function (): void {
    authTestUser();

    $this->get('/');
    $before = $this->app['session.store']->getId();

    $this->postJson('/api/auth/login', authCredentials('user@example.test', 'SecurePass123!'))
        ->assertOk();

    expect($this->app['session.store']->getId())->not->toBe($before);
});

it('returns the same generic failure for an unknown email and a wrong password', function (): void {
    authTestUser();

    $unknown = $this->postJson('/api/auth/login', authCredentials('missing@example.test', 'WrongPass123!'));
    $wrong = $this->postJson('/api/auth/login', authCredentials('user@example.test', 'WrongPass123!'));

    $unknown->assertUnprocessable();
    $wrong->assertUnprocessable();

    expect($unknown->json())->toBe($wrong->json());

    $this->assertGuest();
});

it('refuses authentication for a non-active account', function (string $state): void {
    $user = User::factory()->{$state}()->create(['password' => 'FactoryPass123!']);

    $this->postJson('/api/auth/login', authCredentials($user->email, 'FactoryPass123!'))
        ->assertUnprocessable()
        ->assertJsonPath('errors.email.0', 'The provided credentials are incorrect.');

    $this->assertGuest();
    expect($user->fresh()->last_login_at)->toBeNull();
})->with(['suspended', 'disabled']);

it('locks an account after repeated failed logins', function (): void {
    $user = authTestUser();

    for ($attempt = 0; $attempt < 5; $attempt++) {
        $this->postJson('/api/auth/login', authCredentials('user@example.test', 'WrongPass123!'))
            ->assertUnprocessable();
    }

    expect($user->fresh()->locked_until)->not->toBeNull()
        ->and($user->fresh()->failed_login_attempts)->toBe(0);

    $this->postJson('/api/auth/login', authCredentials('user@example.test', 'SecurePass123!'))
        ->assertUnprocessable()
        ->assertJsonPath('errors.email.0', 'The provided credentials are incorrect.');

    $this->assertGuest();
});

it('allows login after a lockout has expired', function (): void {
    $user = User::factory()->locked(-1)->create(['password' => 'FactoryPass123!']);

    $this->postJson('/api/auth/login', authCredentials($user->email, 'FactoryPass123!'))
        ->assertOk();

    expect($user->fresh()->locked_until)->toBeNull();

    $this->assertAuthenticatedAs($user);
});

it('clears failed attempts after a successful login', function (): void {
    $user = authTestUser();

    $this->postJson('/api/auth/login', authCredentials('user@example.test', 'WrongPass123!'))->assertUnprocessable();
    $this->postJson('/api/auth/login', authCredentials('user@example.test', 'WrongPass123!'))->assertUnprocessable();

    expect($user->fresh()->failed_login_attempts)->toBe(2);

    $this->postJson('/api/auth/login', authCredentials('user@example.test', 'SecurePass123!'))->assertOk();

    expect($user->fresh()->failed_login_attempts)->toBe(0);
});

it('throttles repeated login attempts', function (): void {
    $response = null;

    for ($attempt = 0; $attempt < 10; $attempt++) {
        $response = $this->postJson('/api/auth/login', authCredentials('throttle@example.test', 'WrongPass123!'));
        $response->assertUnprocessable();
    }

    $this->postJson('/api/auth/login', authCredentials('throttle@example.test', 'WrongPass123!'))
        ->assertStatus(Response::HTTP_TOO_MANY_REQUESTS);
});

it('throttles email spraying from a single source address', function (): void {
    for ($attempt = 0; $attempt < 30; $attempt++) {
        $this->postJson('/api/auth/login', authCredentials('spray'.$attempt.'@example.test', 'WrongPass123!'))
            ->assertUnprocessable();
    }

    $this->postJson('/api/auth/login', authCredentials('spray31@example.test', 'WrongPass123!'))
        ->assertStatus(Response::HTTP_TOO_MANY_REQUESTS);
});

it('logs out and invalidates the authenticated session', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson('/api/auth/logout')
        ->assertOk()
        ->assertJsonPath('message', 'Logged out.');

    $this->assertGuest();
});

it('requires authentication to read the current-user endpoint', function (): void {
    $this->getJson('/api/auth/me')->assertUnauthorized();
});

it('does not allow a guest to perform a logout write', function (): void {
    $this->postJson('/api/auth/logout')->assertUnauthorized();

    $this->assertGuest();
});

it('does not let a client-supplied school id at login grant school access', function (): void {
    $owned = School::factory()->create();
    $unowned = School::factory()->create();

    $user = authTestUser();
    $user->schools()->attach($owned);

    $this->postJson('/api/auth/login', authCredentials('user@example.test', AUTH_TEST_SECRET) + [
        'school_id' => $unowned->id,
    ])->assertOk();

    $this->getJson('/api/school')
        ->assertOk()
        ->assertJsonPath('school.id', $owned->id);

    $this->withHeader('X-School-Id', (string) $unowned->id)
        ->getJson('/api/school')
        ->assertForbidden();
});

it('requires an explicit school selection after login for multi-school users', function (): void {
    $first = School::factory()->create();
    $second = School::factory()->create();

    $user = authTestUser();
    $user->schools()->attach($first);
    $user->schools()->attach($second);

    $this->postJson('/api/auth/login', authCredentials('user@example.test', AUTH_TEST_SECRET))->assertOk();

    $this->getJson('/api/school')->assertBadRequest();

    $this->withHeader('X-School-Id', (string) $second->id)
        ->getJson('/api/school')
        ->assertOk()
        ->assertJsonPath('school.id', $second->id);
});

it('exposes only an allow-list of user fields', function (): void {
    $user = authTestUser();

    $login = $this->postJson('/api/auth/login', authCredentials('user@example.test', 'SecurePass123!'))
        ->assertOk()
        ->json('user');

    $me = $this->getJson('/api/auth/me')
        ->assertOk()
        ->json('user');

    $expected = ['id', 'name', 'email', 'status', 'email_verified_at', 'last_login_at'];

    expect(array_keys($login))->toEqualCanonicalizing($expected)
        ->and(array_keys($me))->toEqualCanonicalizing($expected)
        ->and($login)->not->toHaveKey('password')
        ->and($login)->not->toHaveKey('school_id')
        ->and($me)->not->toHaveKey('remember_token')
        ->and($me)->not->toHaveKey('school_id');
});

it('keeps auth endpoints in the web middleware group with login throttling', function (): void {
    $login = Route::getRoutes()->getByName('auth.login.store');
    $logout = Route::getRoutes()->getByName('auth.logout.destroy');
    $me = Route::getRoutes()->getByName('auth.me.show');

    expect($login)->not->toBeNull()
        ->and($logout)->not->toBeNull()
        ->and($me)->not->toBeNull()
        ->and($login->gatherMiddleware())->toContain('web', 'throttle:login', 'throttle:login-ip')
        ->and($logout->gatherMiddleware())->toContain('web', 'auth')
        ->and($me->gatherMiddleware())->toContain('web', 'auth');
});
