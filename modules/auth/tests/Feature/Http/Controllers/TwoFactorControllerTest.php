<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Models\User;
use PragmaRX\Google2FA\Google2FA;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->google2fa = new Google2FA;
});

test('two factor challenge page can be rendered', function () {
    session([
        'auth.two_factor_required' => true,
        'auth.two_factor_user_id'  => 1,
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

test('two factor cannot be enabled if already enabled', function () {
    $user = User::factory()->create([
        'two_factor_secret'       => encrypt($this->google2fa->generateSecretKey()),
        'two_factor_confirmed_at' => now(),
    ]);

    $response = $this->actingAs($user)->post(route('two-factor.enable'));

    $response->assertRedirect();
    $response->assertSessionHasErrors('two_factor');
});

test('two factor QR code can be retrieved', function () {
    $user = User::factory()->create([
        'two_factor_secret' => encrypt($this->google2fa->generateSecretKey()),
    ]);

    $response = $this->actingAs($user)->get(route('two-factor.qr-code'));

    $response->assertOk();
    $response->assertJsonStructure(['svg']);
});

test('two factor QR code returns error when not set up', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('two-factor.qr-code'));

    $response->assertNotFound();
    $response->assertJson(['error' => 'Two-factor authentication is not set up.']);
});

test('two factor secret key can be retrieved', function () {
    $secret = $this->google2fa->generateSecretKey();
    $user   = User::factory()->create([
        'two_factor_secret' => encrypt($secret),
    ]);

    $response = $this->actingAs($user)->get(route('two-factor.secret-key'));

    $response->assertOk();
    $response->assertJson(['secretKey' => $secret]);
});

test('two factor secret key returns error when not set up', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('two-factor.secret-key'));

    $response->assertStatus(400);
    $response->assertJson(['error' => 'Two-factor authentication is not set up.']);
});

test('two factor recovery codes can be retrieved', function () {
    $codes = ['code1', 'code2', 'code3', 'code4', 'code5', 'code6', 'code7', 'code8'];
    $user  = User::factory()->create([
        'two_factor_recovery_codes' => encrypt(json_encode($codes)),
    ]);

    $response = $this->actingAs($user)->get(route('two-factor.recovery-codes'));

    $response->assertOk();
    $response->assertJson($codes);
});

test('two factor recovery codes returns error when not available', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('two-factor.recovery-codes'));

    $response->assertNotFound();
    $response->assertJson(['error' => 'Recovery codes not available.']);
});

test('two factor recovery codes can be regenerated', function () {
    $user = User::factory()->create([
        'two_factor_secret'         => encrypt($this->google2fa->generateSecretKey()),
        'two_factor_confirmed_at'   => now(),
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

test('two factor recovery codes regeneration fails without password confirmation', function () {
    $user = User::factory()->create([
        'two_factor_secret'         => encrypt($this->google2fa->generateSecretKey()),
        'two_factor_confirmed_at'   => now(),
        'two_factor_recovery_codes' => encrypt(json_encode(['old1', 'old2'])),
    ]);

    $response = $this->actingAs($user)->post(route('two-factor.recovery-codes.regenerate'), [
        'password' => 'password',
    ]);

    $response->assertRedirect();
    $response->assertSessionHasErrors('password');
});

test('two factor recovery codes regeneration fails when not fully enabled', function () {
    $user = User::factory()->create([
        'two_factor_secret' => encrypt($this->google2fa->generateSecretKey()),
        // No confirmed_at - not fully enabled
        'two_factor_recovery_codes' => encrypt(json_encode(['old1', 'old2'])),
    ]);

    // Set password confirmation session
    session(['auth.password_confirmed_at' => time()]);

    $response = $this->actingAs($user)->post(route('two-factor.recovery-codes.regenerate'), [
        'password' => 'password',
    ]);

    $response->assertRedirect();
    $response->assertSessionHasErrors('two_factor');
});

test('two factor can be confirmed', function () {
    $secret = $this->google2fa->generateSecretKey();
    $user   = User::factory()->create([
        'two_factor_secret'       => encrypt($secret),
        'two_factor_confirmed_at' => null,
    ]);

    $validCode = $this->google2fa->getCurrentOtp($secret);

    $response = $this->actingAs($user)->post(route('two-factor.confirm'), [
        'code' => $validCode,
    ]);

    $response->assertRedirect();
    expect($user->fresh()->two_factor_confirmed_at)->not->toBeNull();
});

test('two factor confirm returns 404 when no secret', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('two-factor.confirm'), [
        'code' => '123456',
    ]);

    $response->assertNotFound();
});

test('two factor confirm returns error when already confirmed', function () {
    $secret = $this->google2fa->generateSecretKey();
    $user   = User::factory()->create([
        'two_factor_secret'       => encrypt($secret),
        'two_factor_confirmed_at' => now(),
    ]);

    $validCode = $this->google2fa->getCurrentOtp($secret);

    $response = $this->actingAs($user)->post(route('two-factor.confirm'), [
        'code' => $validCode,
    ]);

    $response->assertRedirect();
    $response->assertSessionHasErrors('code');
});

test('two factor cannot be confirmed with invalid code', function () {
    $user = User::factory()->create([
        'two_factor_secret'       => encrypt($this->google2fa->generateSecretKey()),
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
        'two_factor_secret'         => encrypt($this->google2fa->generateSecretKey()),
        'two_factor_confirmed_at'   => now(),
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

test('two factor disable fails without password confirmation', function () {
    $user = User::factory()->create([
        'two_factor_secret'         => encrypt($this->google2fa->generateSecretKey()),
        'two_factor_confirmed_at'   => now(),
        'two_factor_recovery_codes' => encrypt(json_encode(['code1', 'code2'])),
    ]);

    $response = $this->actingAs($user)->delete(route('two-factor.disable'), [
        'password' => 'password',
    ]);

    $response->assertRedirect();
    $response->assertSessionHasErrors('password');
});

test('two factor challenge can be verified', function () {
    $secret = $this->google2fa->generateSecretKey();
    $user   = User::factory()->create([
        'two_factor_secret'       => encrypt($secret),
        'two_factor_confirmed_at' => now(),
    ]);

    session([
        'auth.two_factor_required' => true,
        'auth.two_factor_user_id'  => $user->id,
        'auth.remember'            => false,
    ]);

    $validCode = $this->google2fa->getCurrentOtp($secret);

    $response = $this->post(route('two-factor.verify'), [
        'code' => $validCode,
    ]);

    $response->assertRedirect(route('dashboard'));
    $this->assertAuthenticated();
});

test('two factor verify requires code or recovery code', function () {
    $response = $this->post(route('two-factor.verify'), []);

    $response->assertSessionHasErrors('code');
});

test('two factor verify requires session data', function () {
    $response = $this->post(route('two-factor.verify'), [
        'code' => '123456',
    ]);

    $response->assertSessionHasErrors('code');
});

test('two factor verify handles invalid user', function () {
    session([
        'auth.two_factor_required' => true,
        'auth.two_factor_user_id'  => 999999, // non-existent user
        'auth.remember'            => false,
    ]);

    $response = $this->post(route('two-factor.verify'), [
        'code' => '123456',
    ]);

    $response->assertSessionHasErrors('code');
});

test('two factor verify can use recovery code', function () {
    $user = User::factory()->create([
        'two_factor_secret'         => encrypt($this->google2fa->generateSecretKey()),
        'two_factor_confirmed_at'   => now(),
        'two_factor_recovery_codes' => encrypt(json_encode(['recovery123', 'recovery456'])),
    ]);

    session([
        'auth.two_factor_required' => true,
        'auth.two_factor_user_id'  => $user->id,
        'auth.remember'            => false,
    ]);

    $response = $this->post(route('two-factor.verify'), [
        'recovery_code' => 'recovery123',
    ]);

    $response->assertRedirect(route('dashboard'));
    $this->assertAuthenticated();
});

test('two factor verify fails with invalid recovery code', function () {
    $user = User::factory()->create([
        'two_factor_secret'         => encrypt($this->google2fa->generateSecretKey()),
        'two_factor_confirmed_at'   => now(),
        'two_factor_recovery_codes' => encrypt(json_encode(['recovery123', 'recovery456'])),
    ]);

    session([
        'auth.two_factor_required' => true,
        'auth.two_factor_user_id'  => $user->id,
        'auth.remember'            => false,
    ]);

    $response = $this->post(route('two-factor.verify'), [
        'recovery_code' => 'invalid',
    ]);

    $response->assertSessionHasErrors('recovery_code');
});

test('two factor verify fails with invalid code', function () {
    $user = User::factory()->create([
        'two_factor_secret'       => encrypt($this->google2fa->generateSecretKey()),
        'two_factor_confirmed_at' => now(),
    ]);

    session([
        'auth.two_factor_required' => true,
        'auth.two_factor_user_id'  => $user->id,
        'auth.remember'            => false,
    ]);

    $response = $this->post(route('two-factor.verify'), [
        'code' => '000000',
    ]);

    $response->assertSessionHasErrors('code');
});

test('two factor verify returns json for ajax requests', function () {
    $secret = $this->google2fa->generateSecretKey();
    $user   = User::factory()->create([
        'two_factor_secret'       => encrypt($secret),
        'two_factor_confirmed_at' => now(),
    ]);

    session([
        'auth.two_factor_required' => true,
        'auth.two_factor_user_id'  => $user->id,
        'auth.remember'            => false,
    ]);

    $validCode = $this->google2fa->getCurrentOtp($secret);

    $response = $this->postJson(route('two-factor.verify'), [
        'code' => $validCode,
    ]);

    $response->assertOk();
    $response->assertJson([
        'success'  => true,
        'redirect' => route('dashboard'),
    ]);
});
