<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Modules\Auth\DTO\AuthResponseData;
use Modules\Auth\Models\User;

uses(RefreshDatabase::class);

test('can create auth response data with all fields', function () {
    $now = Carbon::now();

    $authResponse = new AuthResponseData(
        id: 1,
        name: 'John Doe',
        email: 'john@example.com',
        email_verified_at: $now->toISOString(),
        roles: ['admin', 'user'],
        permissions: ['view-users', 'edit-users'],
        last_login_at: $now->toISOString(),
        created_at: $now,
        updated_at: $now
    );

    expect($authResponse->id)->toBe(1);
    expect($authResponse->name)->toBe('John Doe');
    expect($authResponse->email)->toBe('john@example.com');
    expect($authResponse->email_verified_at)->toBe($now->toISOString());
    expect($authResponse->roles)->toBe(['admin', 'user']);
    expect($authResponse->permissions)->toBe(['view-users', 'edit-users']);
    expect($authResponse->last_login_at)->toBe($now->toISOString());
    expect($authResponse->created_at)->toEqual($now);
    expect($authResponse->updated_at)->toEqual($now);
});

test('can create auth response data from user model', function () {
    $user = User::factory()->create([
        'name'              => 'Jane Doe',
        'email'             => 'jane@example.com',
        'email_verified_at' => now(),
        'last_login_at'     => now(),
    ]);

    $authResponse = AuthResponseData::fromModel($user);

    expect($authResponse->id)->toBe($user->id);
    expect($authResponse->name)->toBe('Jane Doe');
    expect($authResponse->email)->toBe('jane@example.com');
    expect($authResponse->email_verified_at)->toBe($user->email_verified_at->toISOString());
    expect($authResponse->roles)->toBeArray();
    expect($authResponse->permissions)->toBeArray();
    expect($authResponse->last_login_at)->toBe($user->last_login_at->toISOString());
    expect($authResponse->created_at)->toEqual($user->created_at);
    expect($authResponse->updated_at)->toEqual($user->updated_at);
});

test('can handle null values for optional fields', function () {
    $user = User::factory()->create([
        'email_verified_at' => null,
        'last_login_at'     => null,
    ]);

    $authResponse = AuthResponseData::fromModel($user);

    expect($authResponse->email_verified_at)->toBeNull();
    expect($authResponse->last_login_at)->toBeNull();
});
