<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Http\Middleware\TwoFactorMiddleware;
use Modules\Auth\Models\User;

uses(RefreshDatabase::class);

test('allows access when user has no two factor enabled', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertStatus(200);
});

test('allows access when two factor is verified', function () {
    $user = User::factory()->create([
        'two_factor_secret'       => encrypt('secret'),
        'two_factor_confirmed_at' => now(),
    ]);

    session(['auth.two_factor_verified' => $user->id]);

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertStatus(200);
});

test('redirects to two factor challenge when not verified', function () {
    Route::middleware(['web', 'auth', TwoFactorMiddleware::class])->get('/protected', function () {
        return 'Protected content';
    });

    $user = User::factory()->create([
        'two_factor_secret'       => encrypt('secret'),
        'two_factor_confirmed_at' => now(),
    ]);

    $response = $this->actingAs($user)->get('/protected');

    $response->assertRedirect(route('two-factor.challenge'));
    expect(session('auth.two_factor_required'))->toBeTrue();
    expect(session('auth.two_factor_user_id'))->toBe($user->id);
});

test('allows guest users through', function () {
    Route::middleware(['web', TwoFactorMiddleware::class])->get('/public', function () {
        return 'Public content';
    });

    $response = $this->get('/public');

    $response->assertStatus(200);
});

test('redirects to two factor challenge for json requests', function () {
    Route::middleware(['web', 'auth', TwoFactorMiddleware::class])->get('/api/protected', function () {
        return response()->json(['data' => 'Protected content']);
    });

    $user = User::factory()->create([
        'two_factor_secret'       => encrypt('secret'),
        'two_factor_confirmed_at' => now(),
    ]);

    $response = $this->actingAs($user)
        ->getJson('/api/protected');

    $response->assertStatus(423)
        ->assertJson([
            'message'             => 'Two-factor authentication required.',
            'two_factor_required' => true,
        ]);

    expect(session('auth.two_factor_required'))->toBeTrue();
    expect(session('auth.two_factor_user_id'))->toBe($user->id);
    $this->assertGuest(); // User should be logged out
});

test('logs out user when two factor not verified', function () {
    Route::middleware(['web', 'auth', TwoFactorMiddleware::class])->get('/protected-page', function () {
        return 'Protected content';
    });

    $user = User::factory()->create([
        'two_factor_secret'       => encrypt('secret'),
        'two_factor_confirmed_at' => now(),
    ]);

    $this->actingAs($user);
    $this->assertAuthenticated();

    $response = $this->get('/protected-page');

    $response->assertRedirect(route('two-factor.challenge'));
    $this->assertGuest(); // User should be logged out
    expect(session('auth.two_factor_required'))->toBeTrue();
    expect(session('auth.two_factor_user_id'))->toBe($user->id);
});

test('allows access when user has no two factor enabled specifically', function () {
    Route::middleware(['web', 'auth', TwoFactorMiddleware::class])->get('/protected-no-2fa', function () {
        return response('Protected content');
    });

    $user = User::factory()->create([
        'two_factor_secret'       => null,
        'two_factor_confirmed_at' => null,
    ]);

    $response = $this->actingAs($user)->get('/protected-no-2fa');

    $response->assertOk();
    $response->assertSee('Protected content');
});

test('allows access when two factor is already verified in session specifically', function () {
    Route::middleware(['web', 'auth', TwoFactorMiddleware::class])->get('/protected-verified', function () {
        return response('Protected content');
    });

    $user = User::factory()->create([
        'two_factor_secret'       => encrypt('secret'),
        'two_factor_confirmed_at' => now(),
    ]);

    // Set session to indicate two-factor is already verified
    session(['auth.two_factor_verified' => $user->id]);

    $response = $this->actingAs($user)->get('/protected-verified');

    $response->assertOk();
    $response->assertSee('Protected content');
});
