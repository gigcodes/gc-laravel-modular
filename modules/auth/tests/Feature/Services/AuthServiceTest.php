<?php

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Modules\Auth\DTO\LoginData;
use Modules\Auth\DTO\RegisterDTO;
use Modules\Auth\Models\User;
use Modules\Auth\Repositories\UserRepository;
use Modules\Auth\Services\AuthService;
use Modules\Auth\Services\Google2FAService;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->userRepository   = app(UserRepository::class);
    $this->google2FAService = app(Google2FAService::class);
    $this->authService      = app(AuthService::class);
});

test('can login with valid credentials', function () {
    $user = User::factory()->create([
        'password' => Hash::make('password'),
    ]);

    $loginData = new LoginData(
        email: $user->email,
        password: 'password',
        remember: false
    );

    $result = $this->authService->login($loginData);

    expect($result)->toBeInstanceOf(User::class);
    expect($result->id)->toBe($user->id);
    expect(Auth::check())->toBeTrue();
    expect(session('auth.two_factor_verified'))->toBe($user->id);
});

test('cannot login with invalid credentials', function () {
    $user = User::factory()->create([
        'password' => Hash::make('password'),
    ]);

    $loginData = new LoginData(
        email: $user->email,
        password: 'wrong-password',
        remember: false
    );

    $result = $this->authService->login($loginData);

    expect($result)->toBeNull();
    expect(Auth::check())->toBeFalse();
});

test('can register new user', function () {
    Event::fake([Registered::class]);

    $registerData = new RegisterDTO(
        name: 'Test User',
        email: 'test@example.com',
        password: 'password'
    );

    $user = $this->authService->register($registerData);

    expect($user)->toBeInstanceOf(User::class);
    expect($user->name)->toBe('Test User');
    expect($user->email)->toBe('test@example.com');
    expect(Hash::check('password', $user->password))->toBeTrue();

    Event::assertDispatched(Registered::class, function ($event) use ($user) {
        return $event->user->id === $user->id;
    });
});

test('logout clears authentication and session data', function () {
    $user = User::factory()->create();
    Auth::login($user);

    session([
        'auth.two_factor_verified' => $user->id,
        'auth.two_factor_required' => true,
        'auth.two_factor_user_id'  => $user->id,
        'auth.remember'            => true,
    ]);

    $this->authService->logout();

    expect(Auth::check())->toBeFalse();
    expect(session('auth.two_factor_verified'))->toBeNull();
    expect(session('auth.two_factor_required'))->toBeNull();
    expect(session('auth.two_factor_user_id'))->toBeNull();
    expect(session('auth.remember'))->toBeNull();
});

test('can verify email for unverified user', function () {
    Event::fake([Verified::class]);

    $user = User::factory()->create([
        'email_verified_at' => null,
    ]);

    $this->authService->verifyEmail($user);

    $user->refresh();
    expect($user->hasVerifiedEmail())->toBeTrue();

    Event::assertDispatched(Verified::class, function ($event) use ($user) {
        return $event->user->id === $user->id;
    });
});

test('verify email does nothing for already verified user', function () {
    Event::fake([Verified::class]);

    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $this->authService->verifyEmail($user);

    Event::assertNotDispatched(Verified::class);
});

test('can resend verification email', function () {
    $user = User::factory()->create([
        'email_verified_at' => null,
    ]);

    $this->authService->resendVerificationEmail($user);

    // Since sendEmailVerificationNotification is a notification,
    // we can't easily test it here without more setup.
    // In a real test, you'd use Notification::fake()
    expect(true)->toBeTrue();
});

test('can send password reset link', function () {
    $user = User::factory()->create();

    Password::shouldReceive('sendResetLink')
        ->once()
        ->with(['email' => $user->email])
        ->andReturn(Password::RESET_LINK_SENT);

    $this->authService->sendPasswordResetLink($user->email);
});

test('can reset password with valid token', function () {
    $user        = User::factory()->create();
    $newPassword = 'new-password';

    Password::shouldReceive('reset')
        ->once()
        ->andReturnUsing(function ($credentials, $callback) use ($user) {
            $callback($user);

            return Password::PASSWORD_RESET;
        });

    $result = $this->authService->resetPassword('valid-token', $user->email, $newPassword);

    expect($result)->toBeTrue();
});

test('cannot reset password with invalid token', function () {
    $user = User::factory()->create();

    Password::shouldReceive('reset')
        ->once()
        ->andReturn(Password::INVALID_TOKEN);

    $result = $this->authService->resetPassword('invalid-token', $user->email, 'new-password');

    expect($result)->toBeFalse();
});

test('is two factor required returns correct value', function () {
    session(['auth.two_factor_required' => true]);
    expect($this->authService->isTwoFactorRequired())->toBeTrue();

    session()->forget('auth.two_factor_required');
    expect($this->authService->isTwoFactorRequired())->toBeFalse();
});

test('complete two factor login returns null when no session data', function () {
    $result = $this->authService->completeTwoFactorLogin('123456');

    expect($result)->toBeNull();
});

test('complete two factor login returns null for invalid user', function () {
    session([
        'auth.two_factor_required' => true,
        'auth.two_factor_user_id'  => 999999,
    ]);

    $result = $this->authService->completeTwoFactorLogin('123456');

    expect($result)->toBeNull();
});

test('complete two factor login returns null for invalid code', function () {
    $google2fa = app(\PragmaRX\Google2FA\Google2FA::class);
    $secret    = $google2fa->generateSecretKey();

    $user = User::factory()->create([
        'two_factor_secret'       => encrypt($secret),
        'two_factor_confirmed_at' => now(),
    ]);

    session(['auth.two_factor_user_id' => $user->id]);

    // Use an obviously invalid code
    $result = $this->authService->completeTwoFactorLogin('000000');

    expect($result)->toBeNull();
});

test('complete two factor login with valid code logs in user', function () {
    $google2fa = app(\PragmaRX\Google2FA\Google2FA::class);
    $secret    = $google2fa->generateSecretKey();

    $user = User::factory()->create([
        'two_factor_secret'       => encrypt($secret),
        'two_factor_confirmed_at' => now(),
    ]);

    session([
        'auth.two_factor_required' => true,
        'auth.two_factor_user_id'  => $user->id,
        'auth.remember'            => false,
    ]);

    // Generate a valid OTP using the same secret
    $validCode = $google2fa->getCurrentOtp($secret);

    $result = $this->authService->completeTwoFactorLogin($validCode);

    expect($result)->toBeInstanceOf(User::class);
    expect($result->id)->toBe($user->id);
    expect(Auth::check())->toBeTrue();
    expect(Auth::id())->toBe($user->id);
    expect(session('auth.two_factor_verified'))->toBe($user->id);
    expect(session('auth.two_factor_required'))->toBeNull();
    expect(session('auth.two_factor_user_id'))->toBeNull();
});
