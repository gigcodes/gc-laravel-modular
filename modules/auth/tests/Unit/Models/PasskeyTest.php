<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Models\Passkey;
use Modules\Auth\Models\User;

uses(RefreshDatabase::class);

test('passkey generates default display name from creation date', function () {
    $user    = User::factory()->create();
    $passkey = Passkey::factory()->create([
        'user_id'    => $user->id,
        'name'       => null, // No custom name
        'created_at' => now()->setDate(2024, 1, 15),
    ]);

    $displayName = $passkey->display_name;

    expect($displayName)->toBe('Passkey created on Jan 15, 2024');
});

test('passkey uses custom name when provided', function () {
    $user    = User::factory()->create();
    $passkey = Passkey::factory()->create([
        'user_id' => $user->id,
        'name'    => 'My Custom Key',
    ]);

    $displayName = $passkey->display_name;

    expect($displayName)->toBe('My Custom Key');
});

test('passkey can find by credential id', function () {
    $user    = User::factory()->create();
    $passkey = Passkey::factory()->create([
        'user_id'       => $user->id,
        'credential_id' => 'test-credential-123',
    ]);

    $found = Passkey::byCredentialId('test-credential-123')->first();

    expect($found)->not->toBeNull();
    expect($found->id)->toBe($passkey->id);
});

test('passkey belongs to user', function () {
    $user    = User::factory()->create();
    $passkey = Passkey::factory()->create([
        'user_id' => $user->id,
    ]);

    expect($passkey->user)->toBeInstanceOf(User::class);
    expect($passkey->user->id)->toBe($user->id);
});

test('passkey can update usage statistics', function () {
    $user    = User::factory()->create();
    $passkey = Passkey::factory()->create([
        'user_id'      => $user->id,
        'sign_count'   => 5,
        'last_used_at' => null,
    ]);

    $passkey->updateUsage(10);

    $passkey->refresh();
    expect($passkey->sign_count)->toBe(10);
    expect($passkey->last_used_at)->not->toBeNull();
});
