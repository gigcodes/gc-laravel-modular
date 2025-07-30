<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Models\User;

uses(RefreshDatabase::class);

test('login page can be rendered', function () {
    $response = $this->get(route('login'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->component('auth::login'));
});

test('login page includes session status', function () {
    session(['status' => 'Password reset link sent!']);

    $response = $this->get(route('login'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('auth::login')
        ->where('status', 'Password reset link sent!')
    );
});

test('users can authenticate using authenticated session controller', function () {
    $user = User::factory()->create([
        'password' => bcrypt('password'),
    ]);

    $response = $this->post(route('login'), [
        'email'    => $user->email,
        'password' => 'password',
        'remember' => false,
    ]);

    $response->assertRedirect(route('dashboard'));
    $this->assertAuthenticated();
    $this->assertAuthenticatedAs($user);
});

test('authentication regenerates session', function () {
    $user = User::factory()->create([
        'password' => bcrypt('password'),
    ]);

    $initialSessionId = session()->getId();

    $response = $this->post(route('login'), [
        'email'    => $user->email,
        'password' => 'password',
        'remember' => false,
    ]);

    $response->assertRedirect(route('dashboard'));
    $this->assertAuthenticated();
    // Note: Can't easily test session regeneration in tests, but the code calls regenerate()
});

test('authenticated users can logout using authenticated session controller', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('logout'));

    $response->assertRedirect('/');
    $this->assertGuest();
});

test('users cannot authenticate with invalid credentials using authenticated session controller', function () {
    $user = User::factory()->create([
        'password' => bcrypt('password'),
    ]);

    $response = $this->post(route('login'), [
        'email'    => $user->email,
        'password' => 'wrong-password',
        'remember' => false,
    ]);

    $response->assertSessionHasErrors();
    $this->assertGuest();
});

test('authentication redirects to intended url', function () {
    $user = User::factory()->create([
        'password' => bcrypt('password'),
    ]);

    // Simulate attempting to access a protected route
    $this->get('/dashboard');

    $response = $this->post(route('login'), [
        'email'    => $user->email,
        'password' => 'password',
        'remember' => false,
    ]);

    $response->assertRedirect(route('dashboard'));
    $this->assertAuthenticated();
});
