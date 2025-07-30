<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Modules\Auth\Events\UserLoggedIn;
use Modules\Auth\Models\User;

uses(RefreshDatabase::class);

test('login page can be rendered', function () {
    $response = $this->get(route('login'));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('auth::login')
    );
});

test('users can authenticate using the login form', function () {
    Event::fake([UserLoggedIn::class]);

    $user = User::factory()->create();

    $response = $this->post(route('login.store'), [
        'email'    => $user->email,
        'password' => 'password',
        'remember' => false,
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard'));
    Event::assertDispatched(UserLoggedIn::class, function ($event) use ($user) {
        return $event->user->id === $user->id;
    });
});

test('users can authenticate with remember me option', function () {
    Event::fake([UserLoggedIn::class]);

    $user = User::factory()->create();

    $response = $this->post(route('login.store'), [
        'email'    => $user->email,
        'password' => 'password',
        'remember' => true,
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard'));
    $response->assertCookie(auth()->guard()->getRecallerName());
});

test('users cannot authenticate with invalid password', function () {
    $user = User::factory()->create();

    $response = $this->post(route('login.store'), [
        'email'    => $user->email,
        'password' => 'wrong-password',
        'remember' => false,
    ]);

    $this->assertGuest();
    $response->assertSessionHasErrors('email');
});

test('users cannot authenticate with non-existent email', function () {
    $response = $this->post(route('login.store'), [
        'email'    => 'nonexistent@example.com',
        'password' => 'password',
        'remember' => false,
    ]);

    $this->assertGuest();
    $response->assertSessionHasErrors('email');
});

test('authenticated users can logout', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = $this->post(route('logout'));

    $this->assertGuest();
    $response->assertRedirect('/');
});

test('check user status returns correct data for existing user', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $response = $this->postJson(route('login.check-status'), [
        'email' => $user->email,
    ]);

    $response->assertOk()
        ->assertJson([
            'userExists'           => true,
            'requiresVerification' => false,
            'hasPasskeys'          => false,
            'passkeyCount'         => 0,
        ]);
});

test('check user status returns correct data for non-existent user', function () {
    $response = $this->postJson(route('login.check-status'), [
        'email' => 'nonexistent@example.com',
    ]);

    $response->assertOk()
        ->assertJson([
            'userExists'           => false,
            'requiresVerification' => false,
            'hasPasskeys'          => false,
            'passkeyCount'         => 0,
        ]);
});

test('check user status returns unverified status for unverified user', function () {
    $user = User::factory()->create([
        'email_verified_at' => null,
    ]);

    $response = $this->postJson(route('login.check-status'), [
        'email' => $user->email,
    ]);

    $response->assertOk()
        ->assertJson([
            'userExists'           => true,
            'requiresVerification' => true,
            'hasPasskeys'          => false,
            'passkeyCount'         => 0,
        ]);
});

test('check user status validates email format', function () {
    $response = $this->postJson(route('login.check-status'), [
        'email' => 'not-an-email',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

test('users with two factor enabled are redirected to challenge', function () {
    Event::fake();

    $user = User::factory()->create([
        'two_factor_secret'       => encrypt('secret'),
        'two_factor_confirmed_at' => now(),
    ]);

    $response = $this->post(route('login.store'), [
        'email'    => $user->email,
        'password' => 'password',
        'remember' => false,
    ]);

    // Should redirect to two-factor challenge instead of dashboard
    $response->assertRedirect(route('two-factor.challenge'));
    $this->assertGuest(); // User should not be authenticated yet
});
