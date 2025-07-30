<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Models\User;
use Modules\Auth\Models\Passkey;

uses(RefreshDatabase::class);

test('can get authentication options', function () {
    $response = $this->postJson(route('passkey.auth.options'));

    $response->assertOk();
    $response->assertJsonStructure([
        'challenge',
        'timeout',
        'userVerification',
        'allowCredentials',
    ]);
});

test('can authenticate with passkey', function () {
    $user = User::factory()->create();
    $passkey = Passkey::factory()->create([
        'user_id' => $user->id,
        'credential_id' => 'test-credential-id',
    ]);

    $response = $this->postJson(route('passkey.auth.verify'), [
        'credential' => [
            'id' => 'test-credential-id',
            'rawId' => 'test-credential-raw-id',
            'type' => 'public-key',
            'response' => [
                'clientDataJSON' => base64_encode(json_encode(['type' => 'webauthn.get'])),
                'authenticatorData' => base64_encode('authenticator-data'),
                'signature' => base64_encode('signature'),
                'userHandle' => base64_encode($user->id),
            ],
        ],
    ]);

    $response->assertOk();
    $response->assertJson(['success' => true]);
    $this->assertAuthenticated();
});

test('can check user passkeys by email', function () {
    $user = User::factory()->create([
        'email' => 'test@example.com',
    ]);
    
    Passkey::factory()->count(2)->create([
        'user_id' => $user->id,
    ]);

    $response = $this->postJson(route('passkey.check-user'), [
        'email' => 'test@example.com',
    ]);

    $response->assertOk();
    $response->assertJson([
        'hasPasskeys' => true,
        'passkeyCount' => 2,
    ]);
});

test('returns false for user without passkeys', function () {
    $user = User::factory()->create([
        'email' => 'test@example.com',
    ]);

    $response = $this->postJson(route('passkey.check-user'), [
        'email' => 'test@example.com',
    ]);

    $response->assertOk();
    $response->assertJson([
        'hasPasskeys' => false,
        'passkeyCount' => 0,
    ]);
});

test('returns false for non-existent user', function () {
    $response = $this->postJson(route('passkey.check-user'), [
        'email' => 'nonexistent@example.com',
    ]);

    $response->assertStatus(404);
});