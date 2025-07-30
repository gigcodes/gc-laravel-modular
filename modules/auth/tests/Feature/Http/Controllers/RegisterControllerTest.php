<?php

use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Modules\Auth\Models\User;

uses(RefreshDatabase::class);

test('registration page can be rendered', function () {
    $response = $this->get(route('register'));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('auth::register')
    );
});

test('new users can register', function () {
    Event::fake([Registered::class]);

    $response = $this->post(route('register.store'), [
        'name'                  => 'Test User',
        'email'                 => 'test@example.com',
        'password'              => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard'));

    $this->assertDatabaseHas('users', [
        'name'  => 'Test User',
        'email' => 'test@example.com',
    ]);

    Event::assertDispatched(Registered::class, function ($event) {
        return $event->user->email === 'test@example.com';
    });
});

test('users cannot register with existing email', function () {
    $existingUser = User::factory()->create();

    $response = $this->post(route('register.store'), [
        'name'                  => 'Test User',
        'email'                 => $existingUser->email,
        'password'              => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertSessionHasErrors('email');
    $this->assertGuest();
});

test('users cannot register with mismatched passwords', function () {
    $response = $this->post(route('register.store'), [
        'name'                  => 'Test User',
        'email'                 => 'test@example.com',
        'password'              => 'password',
        'password_confirmation' => 'different-password',
    ]);

    $response->assertSessionHasErrors('password');
    $this->assertGuest();
});

test('users cannot register with invalid email format', function () {
    $response = $this->post(route('register.store'), [
        'name'                  => 'Test User',
        'email'                 => 'invalid-email',
        'password'              => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertSessionHasErrors('email');
    $this->assertGuest();
});

test('users cannot register with short password', function () {
    $response = $this->post(route('register.store'), [
        'name'                  => 'Test User',
        'email'                 => 'test@example.com',
        'password'              => 'short',
        'password_confirmation' => 'short',
    ]);

    $response->assertSessionHasErrors('password');
    $this->assertGuest();
});

test('users cannot register without name', function () {
    $response = $this->post(route('register.store'), [
        'name'                  => '',
        'email'                 => 'test@example.com',
        'password'              => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertSessionHasErrors('name');
    $this->assertGuest();
});

test('users password is hashed when stored', function () {
    $response = $this->post(route('register.store'), [
        'name'                  => 'Test User',
        'email'                 => 'test@example.com',
        'password'              => 'password',
        'password_confirmation' => 'password',
    ]);

    $user = User::where('email', 'test@example.com')->first();

    expect($user)->not->toBeNull();
    expect($user->password)->not->toBe('password');
    expect(password_verify('password', $user->password))->toBeTrue();
});
