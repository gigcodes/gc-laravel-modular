<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Modules\Auth\Models\User;

uses(RefreshDatabase::class);

test('forgot password page can be rendered', function () {
    $response = $this->get(route('password.request'));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('auth::forgot-password')
    );
});

test('password reset link can be requested', function () {
    Notification::fake();

    $user = User::factory()->create();

    $response = $this->post(route('password.email'), [
        'email' => $user->email,
    ]);

    $response->assertSessionHas('status', 'auth::messages.password_reset_link_sent');
});

test('password reset link cannot be requested with invalid email', function () {
    $response = $this->post(route('password.email'), [
        'email' => 'invalid-email',
    ]);

    $response->assertSessionHasErrors('email');
});

test('password reset link cannot be requested with non-existent email', function () {
    $response = $this->post(route('password.email'), [
        'email' => 'nonexistent@example.com',
    ]);

    $response->assertSessionHas('status', 'auth::messages.password_reset_link_sent');
});
