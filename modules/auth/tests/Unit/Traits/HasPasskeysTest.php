<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Models\Passkey;
use Modules\Auth\Models\User;

uses(RefreshDatabase::class);

test('user can have passkeys relationship', function () {
    $user = User::factory()->create();

    expect($user->passkeys())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class);
});

test('can check if user has passkeys', function () {
    $userWithoutPasskeys = User::factory()->create();
    $userWithPasskeys    = User::factory()->create();

    Passkey::factory()->create(['user_id' => $userWithPasskeys->id]);

    expect($userWithoutPasskeys->hasPasskeys())->toBeFalse();
    expect($userWithPasskeys->hasPasskeys())->toBeTrue();
});

test('can get passkey username', function () {
    $user = User::factory()->create(['email' => 'test@example.com']);

    expect($user->getPasskeyUsername())->toBe('test@example.com');
});

test('can get passkey user id', function () {
    $user = User::factory()->create();

    expect($user->getPasskeyUserId())->toBe($user->id);
});

test('can get passkey display name', function () {
    $user = User::factory()->create(['name' => 'John Doe']);

    expect($user->getPasskeyDisplayName())->toBe('John Doe');
});

test('can get passkey user icon', function () {
    $user = User::factory()->create(['profile_photo_path' => null]);

    // Without profile photo path set, should return the default profile photo URL
    $icon = $user->getPasskeyUserIcon();
    expect($icon)->not->toBeNull();
    expect($icon)->toContain('ui-avatars.com');
});

test('can find passkey by credential id', function () {
    $user    = User::factory()->create();
    $passkey = Passkey::factory()->create([
        'user_id'       => $user->id,
        'credential_id' => 'test-credential-123',
    ]);

    $found    = $user->findPasskeyByCredentialId('test-credential-123');
    $notFound = $user->findPasskeyByCredentialId('non-existent');

    expect($found)->toBeInstanceOf(Passkey::class);
    expect($found->id)->toBe($passkey->id);
    expect($notFound)->toBeNull();
});

test('can create passkey for user', function () {
    $user = User::factory()->create();

    $passkeyData = [
        'name'          => 'My Security Key',
        'credential_id' => 'credential-123',
        'public_key'    => 'public-key-data',
        'sign_count'    => 0,
    ];

    $passkey = $user->createPasskey($passkeyData);

    expect($passkey)->toBeInstanceOf(Passkey::class);
    expect($passkey->user_id)->toBe($user->id);
    expect($passkey->name)->toBe('My Security Key');
    expect($passkey->credential_id)->toBe('credential-123');
});

test('can delete passkey by id', function () {
    $user    = User::factory()->create();
    $passkey = Passkey::factory()->create(['user_id' => $user->id]);

    $result = $user->deletePasskey($passkey->id);

    expect($result)->toBeTrue();
    expect(Passkey::find($passkey->id))->toBeNull();
});

test('delete passkey returns false for non-existent passkey', function () {
    $user = User::factory()->create();

    $result = $user->deletePasskey('non-existent-id');

    expect($result)->toBeFalse();
});

test('can get passkey credentials for authentication', function () {
    $user = User::factory()->create();

    $passkey1 = Passkey::factory()->create([
        'user_id'       => $user->id,
        'credential_id' => 'cred-1',
        'public_key'    => 'pub-key-1',
        'sign_count'    => 5,
    ]);

    $passkey2 = Passkey::factory()->create([
        'user_id'       => $user->id,
        'credential_id' => 'cred-2',
        'public_key'    => 'pub-key-2',
        'sign_count'    => 10,
    ]);

    $credentials = $user->getPasskeyCredentials();

    expect($credentials)->toHaveCount(2);
    expect($credentials[0])->toHaveKeys(['id', 'publicKey', 'signCount']);
    expect($credentials[0]['id'])->toBe('cred-2'); // Ordered by created_at desc
    expect($credentials[0]['publicKey'])->toBe('pub-key-2');
    expect($credentials[0]['signCount'])->toBe(10);
    expect($credentials[1]['id'])->toBe('cred-1');
});
