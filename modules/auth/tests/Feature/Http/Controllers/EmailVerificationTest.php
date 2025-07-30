<?php

use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Modules\Auth\Models\User;

uses(RefreshDatabase::class);

test('email verification screen can be rendered', function () {
    $user = User::factory()->create([
        'email_verified_at' => null,
    ]);

    $response = $this->actingAs($user)->get(route('verification.notice'));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('auth::verify-email')
    );
});

test('email can be verified', function () {
    Event::fake();

    $user = User::factory()->create([
        'email_verified_at' => null,
    ]);

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1($user->email)]
    );

    $response = $this->actingAs($user)->get($verificationUrl);

    Event::assertDispatched(Verified::class);
    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
    $response->assertRedirect(route('dashboard').'?verified=1');
});

test('email is not verified with invalid hash', function () {
    $user = User::factory()->create([
        'email_verified_at' => null,
    ]);

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1('wrong-email')]
    );

    $this->actingAs($user)->get($verificationUrl);

    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
});

test('new verification notification can be sent', function () {
    $user = User::factory()->create([
        'email_verified_at' => null,
    ]);

    $response = $this->actingAs($user)->post(route('verification.send'));

    $response->assertSessionHas('success', 'Verification link sent!');
});

test('verified users are redirected from verification page', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $response = $this->actingAs($user)->get(route('verification.notice'));

    $response->assertRedirect(route('dashboard'));
});

test('verified users cannot send new verification notification', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $response = $this->actingAs($user)->post(route('verification.send'));

    $response->assertRedirect(route('dashboard'));
    $response->assertSessionHas('success', 'Email already verified.');
});

test('already verified users get redirected directly', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1($user->email)]
    );

    $response = $this->actingAs($user)->get($verificationUrl);

    $response->assertRedirect(route('dashboard').'?verified=1');
});
