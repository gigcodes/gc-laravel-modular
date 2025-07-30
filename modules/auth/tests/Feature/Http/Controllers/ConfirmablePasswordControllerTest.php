<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Models\User;

uses(RefreshDatabase::class);

test('confirm password screen can be rendered', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('password.confirm'));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('auth::confirm-password')
    );
});

test('password can be confirmed', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('password.confirm.store'), [
        'password' => 'password',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('auth.password_confirmed_at');
});

test('password is not confirmed with incorrect password', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('password.confirm.store'), [
        'password' => 'wrong-password',
    ]);

    $response->assertSessionHasErrors('password');
});

test('password confirmation status can be checked', function () {
    $user = User::factory()->create();

    // Not confirmed yet
    $response = $this->actingAs($user)->get(route('password.confirmation'));
    $response->assertJson(['confirmed' => false]);

    // Confirm password
    session(['auth.password_confirmed_at' => time()]);

    $response = $this->actingAs($user)->get(route('password.confirmation'));
    $response->assertJson(['confirmed' => true]);
});
