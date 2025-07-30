<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Models\Passkey;
use Modules\Auth\Models\User;

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
    $user    = User::factory()->create();
    $passkey = Passkey::factory()->create([
        'user_id'       => $user->id,
        'credential_id' => 'test-credential-id',
    ]);

    $response = $this->postJson(route('passkey.auth.verify'), [
        'credential' => [
            'id'       => 'test-credential-id',
            'rawId'    => 'test-credential-raw-id',
            'type'     => 'public-key',
            'response' => [
                'clientDataJSON'    => base64_encode(json_encode(['type' => 'webauthn.get'])),
                'authenticatorData' => base64_encode('authenticator-data'),
                'signature'         => base64_encode('signature'),
                'userHandle'        => base64_encode($user->id),
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
        'hasPasskeys'  => true,
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
        'hasPasskeys'  => false,
        'passkeyCount' => 0,
    ]);
});

test('returns false for non-existent user', function () {
    $response = $this->postJson(route('passkey.check-user'), [
        'email' => 'nonexistent@example.com',
    ]);

    $response->assertStatus(404);
});

test('can get authentication options with email provided', function () {
    $user = User::factory()->create();
    Passkey::factory()->create(['user_id' => $user->id]);

    $response = $this->postJson(route('passkey.auth.options'), [
        'email' => $user->email,
    ]);

    $response->assertOk();
    $response->assertJsonStructure([
        'challenge',
        'timeout',
        'userVerification',
        'allowCredentials',
    ]);
});

test('passkey authentication fails with invalid credentials', function () {
    $response = $this->postJson(route('passkey.auth.verify'), [
        'credential' => [
            'id'       => 'invalid-id',
            'response' => 'invalid-response',
        ],
    ]);

    $response->assertStatus(401);
    $response->assertJson([
        'success' => false,
        'message' => 'Invalid passkey or user not found.',
    ]);
});

test('passkey authentication handles validation errors', function () {
    // This will trigger a validation error
    $response = $this->postJson(route('passkey.auth.verify'), [
        'credential' => 'invalid-data-type', // Should be array
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['credential']);
});

test('passkey authentication returns redirect for non-json requests', function () {
    $user    = User::factory()->create();
    $passkey = Passkey::factory()->create(['user_id' => $user->id]);

    $response = $this->post(route('passkey.auth.verify'), [
        'credential' => [
            'id'        => $passkey->credential_id,
            'signCount' => 1,
        ],
    ]);

    $response->assertRedirect(route('dashboard'));
    $this->assertAuthenticated();
});

test('passkey authentication handles service exceptions', function () {
    $this->mock(\Modules\Auth\Services\PasskeyService::class, function ($mock) {
        $mock->shouldReceive('verifyAuthentication')
            ->andThrow(new \Exception('Service error'));
    });

    $response = $this->postJson(route('passkey.auth.verify'), [
        'credential' => [
            'id'       => 'test-id',
            'response' => 'test-response',
        ],
    ]);

    $response->assertStatus(422);
    $response->assertJson([
        'success' => false,
        'message' => 'Authentication failed: Service error',
    ]);
});
