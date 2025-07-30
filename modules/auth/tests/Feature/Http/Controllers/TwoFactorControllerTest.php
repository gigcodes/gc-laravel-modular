<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Models\User;
use PragmaRX\Google2FA\Google2FA;
use Illuminate\Support\Facades\Crypt;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->google2fa = new Google2FA();
});

test('two factor challenge page can be rendered', function () {
    session([
        'auth.two_factor_required' => true,
        'auth.two_factor_user_id' => 1,
    ]);

    $response = $this->get(route('two-factor.challenge'));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('auth::two-factor/challenge')
    );
});

test('two factor can be enabled', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('two-factor.enable'));

    $response->assertRedirect();
    expect($user->fresh()->two_factor_secret)->not->toBeNull();
});

test('two factor QR code can be retrieved', function () {
    $user = User::factory()->create([
        'two_factor_secret' => encrypt($this->google2fa->generateSecretKey()),
    ]);

    $response = $this->actingAs($user)->get(route('two-factor.qr-code'));

    $response->assertOk();
    $response->assertJsonStructure(['svg']);
});

test('two factor secret key can be retrieved', function () {
    $secret = $this->google2fa->generateSecretKey();
    $user = User::factory()->create([
        'two_factor_secret' => encrypt($secret),
    ]);

    $response = $this->actingAs($user)->get(route('two-factor.secret-key'));

    $response->assertOk();
    $response->assertJson(['secretKey' => $secret]);
});

test('two factor recovery codes can be retrieved', function () {
    $codes = ['code1', 'code2', 'code3', 'code4', 'code5', 'code6', 'code7', 'code8'];
    $user = User::factory()->create([
        'two_factor_recovery_codes' => encrypt(json_encode($codes)),
    ]);

    $response = $this->actingAs($user)->get(route('two-factor.recovery-codes'));

    $response->assertOk();
    $response->assertJson($codes);
});

test('two factor recovery codes can be regenerated', function () {
    $user = User::factory()->create([
        'two_factor_secret' => encrypt($this->google2fa->generateSecretKey()),
        'two_factor_confirmed_at' => now(),
        'two_factor_recovery_codes' => encrypt(json_encode(['old1', 'old2'])),
    ]);

    // Set password confirmation session
    session(['auth.password_confirmed_at' => time()]);

    $response = $this->actingAs($user)->post(route('two-factor.recovery-codes.regenerate'), [
        'password' => 'password',
    ]);

    $response->assertRedirect();
    
    $newCodes = json_decode(decrypt($user->fresh()->two_factor_recovery_codes), true);
    expect($newCodes)->toHaveCount(8);
    expect($newCodes)->not->toContain('old1');
});

test('two factor can be confirmed', function () {
    $secret = $this->google2fa->generateSecretKey();
    $user = User::factory()->create([
        'two_factor_secret' => encrypt($secret),
        'two_factor_confirmed_at' => null,
    ]);

    $validCode = $this->google2fa->getCurrentOtp($secret);

    $response = $this->actingAs($user)->post(route('two-factor.confirm'), [
        'code' => $validCode,
    ]);

    $response->assertRedirect();
    expect($user->fresh()->two_factor_confirmed_at)->not->toBeNull();
});

test('two factor cannot be confirmed with invalid code', function () {
    $user = User::factory()->create([
        'two_factor_secret' => encrypt($this->google2fa->generateSecretKey()),
        'two_factor_confirmed_at' => null,
    ]);

    $response = $this->actingAs($user)->post(route('two-factor.confirm'), [
        'code' => '000000',
    ]);

    $response->assertSessionHasErrors('code');
    expect($user->fresh()->two_factor_confirmed_at)->toBeNull();
});

test('two factor can be disabled', function () {
    $user = User::factory()->create([
        'two_factor_secret' => encrypt($this->google2fa->generateSecretKey()),
        'two_factor_confirmed_at' => now(),
        'two_factor_recovery_codes' => encrypt(json_encode(['code1', 'code2'])),
    ]);

    // Set password confirmation session
    session(['auth.password_confirmed_at' => time()]);

    $response = $this->actingAs($user)->delete(route('two-factor.disable'), [
        'password' => 'password',
    ]);

    $response->assertRedirect();
    $user->refresh();
    expect($user->two_factor_secret)->toBeNull();
    expect($user->two_factor_confirmed_at)->toBeNull();
    expect($user->two_factor_recovery_codes)->toBeNull();
});

test('two factor challenge can be verified', function () {
    $secret = $this->google2fa->generateSecretKey();
    $user = User::factory()->create([
        'two_factor_secret' => encrypt($secret),
        'two_factor_confirmed_at' => now(),
    ]);

    session([
        'auth.two_factor_required' => true,
        'auth.two_factor_user_id' => $user->id,
        'auth.remember' => false,
    ]);

    $validCode = $this->google2fa->getCurrentOtp($secret);

    $response = $this->post(route('two-factor.verify'), [
        'code' => $validCode,
    ]);

    $response->assertRedirect(route('dashboard'));
    $this->assertAuthenticated();
});