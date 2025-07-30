<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Models\Passkey;
use Modules\Auth\Models\User;

uses(RefreshDatabase::class);

test('can get user passkeys', function () {
    $user = User::factory()->create();

    Passkey::factory()->count(3)->create([
        'user_id' => $user->id,
    ]);

    $response = $this->actingAs($user)->get(route('passkeys.index'));

    $response->assertOk();
    $response->assertJsonCount(3, 'passkeys');
});

test('can get registration options', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('passkeys.registration.options'));

    $response->assertOk();
    $response->assertJsonStructure([
        'challenge',
        'rp',
        'user',
        'pubKeyCredParams',
        'authenticatorSelection',
        'timeout',
        'excludeCredentials',
    ]);
});

test('can register a passkey', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('passkeys.store'), [
        'name'       => 'My Security Key',
        'credential' => [
            'id'         => 'credential-id',
            'publicKey'  => base64_encode('public-key-data'),
            'signCount'  => 0,
            'transports' => ['usb'],
        ],
    ]);

    $response->assertOk();
    $this->assertDatabaseHas('passkeys', [
        'user_id' => $user->id,
        'name'    => 'My Security Key',
    ]);
});

test('can update passkey name', function () {
    $user    = User::factory()->create();
    $passkey = Passkey::factory()->create([
        'user_id' => $user->id,
        'name'    => 'Old Name',
    ]);

    $response = $this->actingAs($user)->put(route('passkeys.update', $passkey), [
        'name' => 'New Name',
    ]);

    $response->assertOk();
    expect($passkey->fresh()->name)->toBe('New Name');
});

test('cannot update another users passkey', function () {
    $user      = User::factory()->create();
    $otherUser = User::factory()->create();
    $passkey   = Passkey::factory()->create([
        'user_id' => $otherUser->id,
    ]);

    $response = $this->actingAs($user)->put(route('passkeys.update', $passkey), [
        'name' => 'New Name',
    ]);

    $response->assertNotFound();
});

test('can delete passkey', function () {
    $user    = User::factory()->create();
    $passkey = Passkey::factory()->create([
        'user_id' => $user->id,
    ]);

    $response = $this->actingAs($user)->delete(route('passkeys.destroy', $passkey));

    $response->assertOk();
    $this->assertDatabaseMissing('passkeys', [
        'id' => $passkey->id,
    ]);
});

test('cannot delete another users passkey', function () {
    $user      = User::factory()->create();
    $otherUser = User::factory()->create();
    $passkey   = Passkey::factory()->create([
        'user_id' => $otherUser->id,
    ]);

    $response = $this->actingAs($user)->delete(route('passkeys.destroy', $passkey));

    $response->assertNotFound();
});

test('registration options redirects unauthenticated user', function () {
    $response = $this->post(route('passkeys.registration.options'));

    $response->assertRedirect(route('login'));
});

test('registration options handles service exceptions', function () {
    $user = User::factory()->create();

    // Mock the service to throw an exception
    $this->mock(\Modules\Auth\Services\PasskeyService::class, function ($mock) {
        $mock->shouldReceive('generateRegistrationOptions')
            ->andThrow(new \Exception('Service error'));
    });

    $response = $this->actingAs($user)->post(route('passkeys.registration.options'));

    $response->assertStatus(500);
    $response->assertJson([
        'success' => false,
        'message' => 'Failed to generate registration options: Service error',
    ]);
});

test('store passkey handles service exceptions', function () {
    $user = User::factory()->create();

    // Mock the service to throw an exception
    $this->mock(\Modules\Auth\Services\PasskeyService::class, function ($mock) {
        $mock->shouldReceive('storePasskey')
            ->andThrow(new \Exception('Storage error'));
    });

    $response = $this->actingAs($user)->post(route('passkeys.store'), [
        'name'       => 'Test Key',
        'credential' => [
            'id'        => 'test-id',
            'publicKey' => base64_encode('test-key'),
            'signCount' => 0,
        ],
    ]);

    $response->assertStatus(422);
    $response->assertJson([
        'success' => false,
        'message' => 'Failed to register passkey: Storage error',
    ]);
});

test('store passkey redirects unauthenticated user', function () {
    $response = $this->post(route('passkeys.store'), [
        'name'       => 'Test Key',
        'credential' => [
            'id'        => 'test-id',
            'publicKey' => base64_encode('test-key'),
        ],
    ]);

    $response->assertRedirect(route('login'));
});

test('registration options returns json error for unauthenticated json request', function () {
    $response = $this->postJson(route('passkeys.registration.options'));

    $response->assertStatus(401);
    $response->assertJson(['message' => 'Unauthenticated.']);
});

test('store passkey returns json error for unauthenticated json request', function () {
    $response = $this->postJson(route('passkeys.store'), [
        'name'       => 'Test Key',
        'credential' => [
            'id'        => 'test-id',
            'publicKey' => base64_encode('test-key'),
        ],
    ]);

    $response->assertStatus(401);
    $response->assertJson(['message' => 'Unauthenticated.']);
});
