<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Events\UserLoggedOut;
use Modules\Auth\Models\User;

uses(RefreshDatabase::class);

test('user logged out event can be created', function () {
    $user = User::factory()->create();

    $event = new UserLoggedOut($user);

    expect($event->user)->toBeInstanceOf(User::class);
    expect($event->user->id)->toBe($user->id);
});

test('user logged out event has correct properties', function () {
    $user = User::factory()->create([
        'name'  => 'John Doe',
        'email' => 'john@example.com',
    ]);

    $event = new UserLoggedOut($user);

    expect($event->user->name)->toBe('John Doe');
    expect($event->user->email)->toBe('john@example.com');
});
