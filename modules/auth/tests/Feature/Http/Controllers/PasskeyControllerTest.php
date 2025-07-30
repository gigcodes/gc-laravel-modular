<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Models\User;
use Modules\Auth\Models\Passkey;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialSource;

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
        'name' => 'My Security Key',
        'credential' => [
            'id' => 'credential-id',
            'publicKey' => base64_encode('public-key-data'),
            'signCount' => 0,
            'transports' => ['usb'],
        ],
    ]);

    $response->assertOk();
    $this->assertDatabaseHas('passkeys', [
        'user_id' => $user->id,
        'name' => 'My Security Key',
    ]);
});

test('can update passkey name', function () {
    $user = User::factory()->create();
    $passkey = Passkey::factory()->create([
        'user_id' => $user->id,
        'name' => 'Old Name',
    ]);

    $response = $this->actingAs($user)->put(route('passkeys.update', $passkey), [
        'name' => 'New Name',
    ]);

    $response->assertOk();
    expect($passkey->fresh()->name)->toBe('New Name');
});

test('cannot update another users passkey', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $passkey = Passkey::factory()->create([
        'user_id' => $otherUser->id,
    ]);

    $response = $this->actingAs($user)->put(route('passkeys.update', $passkey), [
        'name' => 'New Name',
    ]);

    $response->assertNotFound();
});

test('can delete passkey', function () {
    $user = User::factory()->create();
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
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $passkey = Passkey::factory()->create([
        'user_id' => $otherUser->id,
    ]);

    $response = $this->actingAs($user)->delete(route('passkeys.destroy', $passkey));

    $response->assertNotFound();
});