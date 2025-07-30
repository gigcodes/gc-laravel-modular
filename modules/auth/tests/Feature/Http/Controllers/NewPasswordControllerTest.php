<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Password;
use Modules\Auth\Models\User;

uses(RefreshDatabase::class);

test('reset password page can be rendered', function () {
    $user  = User::factory()->create();
    $token = Password::createToken($user);

    $response = $this->get(route('password.reset', ['token' => $token]));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('auth::reset-password')
        ->has('token')
        ->has('email')
    );
});

test('password can be reset with valid token', function () {
    $user  = User::factory()->create();
    $token = Password::createToken($user);

    $response = $this->post(route('password.store'), [
        'token'                 => $token,
        'email'                 => $user->email,
        'password'              => 'new-password',
        'password_confirmation' => 'new-password',
    ]);

    $response->assertRedirect(route('login'));
    $response->assertSessionHas('status', 'auth::messages.password_reset');
});

test('password cannot be reset with invalid token', function () {
    $user = User::factory()->create();

    $response = $this->post(route('password.store'), [
        'token'                 => 'invalid-token',
        'email'                 => $user->email,
        'password'              => 'new-password',
        'password_confirmation' => 'new-password',
    ]);

    $response->assertSessionHasErrors('email');
});

test('password cannot be reset with mismatched passwords', function () {
    $user  = User::factory()->create();
    $token = Password::createToken($user);

    $response = $this->post(route('password.store'), [
        'token'                 => $token,
        'email'                 => $user->email,
        'password'              => 'new-password',
        'password_confirmation' => 'different-password',
    ]);

    $response->assertSessionHasErrors('password');
});

test('password cannot be reset with invalid email', function () {
    $user  = User::factory()->create();
    $token = Password::createToken($user);

    $response = $this->post(route('password.store'), [
        'token'                 => $token,
        'email'                 => 'wrong@email.com',
        'password'              => 'new-password',
        'password_confirmation' => 'new-password',
    ]);

    $response->assertSessionHasErrors('email');
});
