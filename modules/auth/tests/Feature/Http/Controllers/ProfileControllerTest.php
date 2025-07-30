<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Models\User;

uses(RefreshDatabase::class);

test('profile page can be rendered', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('profile.edit'));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('auth::settings/profile')
        ->has('mustVerifyEmail')
        ->has('confirmsTwoFactorAuthentication')
    );
});

test('profile information can be updated', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->patch(route('profile.update'), [
        'name'  => 'Updated Name',
        'email' => 'updated@example.com',
    ]);

    $response->assertRedirect(route('profile.edit'));

    $user->refresh();

    expect($user->name)->toBe('Updated Name');
    expect($user->email)->toBe('updated@example.com');
    expect($user->email_verified_at)->toBeNull();
});

test('email verification status is unchanged when email is not updated', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $response = $this->actingAs($user)->patch(route('profile.update'), [
        'name'  => 'Updated Name',
        'email' => $user->email,
    ]);

    $response->assertRedirect(route('profile.edit'));

    $user->refresh();

    expect($user->name)->toBe('Updated Name');
    expect($user->email_verified_at)->not->toBeNull();
});

test('user cannot update profile with invalid email', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->patch(route('profile.update'), [
        'name'  => 'Updated Name',
        'email' => 'invalid-email',
    ]);

    $response->assertSessionHasErrors('email');
});

test('user cannot update profile with existing email', function () {
    $user      = User::factory()->create();
    $otherUser = User::factory()->create();

    $response = $this->actingAs($user)->patch(route('profile.update'), [
        'name'  => 'Updated Name',
        'email' => $otherUser->email,
    ]);

    $response->assertSessionHasErrors('email');
});

test('user cannot update profile without name', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->patch(route('profile.update'), [
        'name'  => '',
        'email' => $user->email,
    ]);

    $response->assertSessionHasErrors('name');
});

test('user can delete their account', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->delete(route('profile.destroy'), [
        'password' => 'password',
    ]);

    $response->assertRedirect('/');

    $this->assertGuest();
    $this->assertDatabaseMissing('users', [
        'id' => $user->id,
    ]);
});

test('user cannot delete account with incorrect password', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->delete(route('profile.destroy'), [
        'password' => 'wrong-password',
    ]);

    $response->assertSessionHasErrors('password');

    $this->assertNotNull($user->fresh());
});

test('unauthenticated users cannot access profile page', function () {
    $response = $this->get(route('profile.edit'));

    $response->assertRedirect(route('login'));
});

test('unauthenticated users cannot update profile', function () {
    $response = $this->patch(route('profile.update'), [
        'name'  => 'Test',
        'email' => 'test@example.com',
    ]);

    $response->assertRedirect(route('login'));
});

test('unauthenticated users cannot delete account', function () {
    $response = $this->delete(route('profile.destroy'), [
        'password' => 'password',
    ]);

    $response->assertRedirect(route('login'));
});
