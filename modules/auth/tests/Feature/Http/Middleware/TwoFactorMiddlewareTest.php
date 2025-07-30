<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Models\User;
use Modules\Auth\Http\Middleware\TwoFactorMiddleware;

uses(RefreshDatabase::class);

test('allows access when user has no two factor enabled', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertStatus(200);
});

test('allows access when two factor is verified', function () {
    $user = User::factory()->create([
        'two_factor_secret' => encrypt('secret'),
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
        'two_factor_secret' => encrypt('secret'),
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