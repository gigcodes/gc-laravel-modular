<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Modules\Auth\Models\User;

uses(RefreshDatabase::class);

test('password edit page can be rendered', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('password.edit'));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('auth::settings/password')
    );
});

test('password can be updated', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->put(route('password.update'), [
        'current_password'      => 'password',
        'password'              => 'new-password',
        'password_confirmation' => 'new-password',
    ]);

    $response->assertRedirect();
    expect(Hash::check('new-password', $user->fresh()->password))->toBeTrue();
});

test('correct current password must be provided to update password', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->put(route('password.update'), [
        'current_password'      => 'wrong-password',
        'password'              => 'new-password',
        'password_confirmation' => 'new-password',
    ]);

    $response->assertSessionHasErrors('current_password');
});

test('password must be confirmed', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->put(route('password.update'), [
        'current_password'      => 'password',
        'password'              => 'new-password',
        'password_confirmation' => 'different-password',
    ]);

    $response->assertSessionHasErrors('password');
});

test('password must meet minimum requirements', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->put(route('password.update'), [
        'current_password'      => 'password',
        'password'              => 'short',
        'password_confirmation' => 'short',
    ]);

    $response->assertSessionHasErrors('password');
});
