<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Models\User;
use PragmaRX\Google2FA\Google2FA;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->google2fa = new Google2FA;
});

test('can get recovery codes', function () {
    $codes = ['CODE1-CODE1', 'CODE2-CODE2'];
    $user  = User::factory()->create([
        'two_factor_recovery_codes' => encrypt(json_encode($codes)),
    ]);

    $recoveryCodes = $user->recoveryCodes();

    expect($recoveryCodes)->toBe($codes);
});

test('can get recovery codes when none exist', function () {
    $user = User::factory()->create([
        'two_factor_recovery_codes' => null,
    ]);

    $recoveryCodes = $user->recoveryCodes();

    expect($recoveryCodes)->toBe([]);
});

test('can get two factor QR code SVG', function () {
    $secret = $this->google2fa->generateSecretKey();
    $user   = User::factory()->create([
        'two_factor_secret' => encrypt($secret),
        'email'             => 'test@example.com',
    ]);

    $svg = $user->twoFactorQrCodeSvg();

    expect($svg)->toBeString();
    expect($svg)->toContain('<svg');
});

test('returns empty string for QR code SVG when no secret', function () {
    $user = User::factory()->create([
        'two_factor_secret' => null,
    ]);

    $svg = $user->twoFactorQrCodeSvg();

    expect($svg)->toBe('');
});

test('can get two factor QR code URL', function () {
    $secret = $this->google2fa->generateSecretKey();
    $user   = User::factory()->create([
        'two_factor_secret' => encrypt($secret),
        'email'             => 'test@example.com',
    ]);

    $url = $user->twoFactorQrCodeUrl();

    expect($url)->toBeString();
    expect($url)->toContain('otpauth://totp/');
    expect($url)->toContain(urlencode($user->email));
});

test('returns empty string for QR code URL when no secret', function () {
    $user = User::factory()->create([
        'two_factor_secret' => null,
    ]);

    $url = $user->twoFactorQrCodeUrl();

    expect($url)->toBe('');
});

test('can check if two factor authentication is enabled', function () {
    $userWithout2FA = User::factory()->create();
    $userWith2FA    = User::factory()->create([
        'two_factor_secret'       => encrypt($this->google2fa->generateSecretKey()),
        'two_factor_confirmed_at' => now(),
    ]);

    expect($userWithout2FA->hasEnabledTwoFactorAuthentication())->toBeFalse();
    expect($userWith2FA->hasEnabledTwoFactorAuthentication())->toBeTrue();
});

test('can check if two factor authentication is confirmed', function () {
    $unconfirmedUser = User::factory()->create([
        'two_factor_secret'       => encrypt($this->google2fa->generateSecretKey()),
        'two_factor_confirmed_at' => null,
    ]);

    $confirmedUser = User::factory()->create([
        'two_factor_secret'       => encrypt($this->google2fa->generateSecretKey()),
        'two_factor_confirmed_at' => now(),
    ]);

    expect($unconfirmedUser->twoFactorAuthenticationConfirmed())->toBeFalse();
    expect($confirmedUser->twoFactorAuthenticationConfirmed())->toBeTrue();
});

test('can get decrypted two factor secret', function () {
    $secret = $this->google2fa->generateSecretKey();
    $user   = User::factory()->create([
        'two_factor_secret' => encrypt($secret),
    ]);

    $decryptedSecret = $user->getDecryptedTwoFactorSecret();

    expect($decryptedSecret)->toBe($secret);
});

test('returns null for decrypted secret when no secret exists', function () {
    $user = User::factory()->create([
        'two_factor_secret' => null,
    ]);

    $decryptedSecret = $user->getDecryptedTwoFactorSecret();

    expect($decryptedSecret)->toBeNull();
});

test('can replace recovery code', function () {
    $user = User::factory()->create([
        'two_factor_recovery_codes' => json_encode([
            'ABCD1234',
            'EFGH5678',
        ]),
    ]);

    // Mock the service to simulate successful verification
    $this->mock(\Modules\Auth\Services\Google2FAService::class, function ($mock) use ($user) {
        $mock->shouldReceive('verifyRecoveryCode')
            ->with($user, 'ABCD1234')
            ->once()
            ->andReturn(true);
    });

    $user->replaceRecoveryCode('ABCD1234');
    // Test passes if no exception is thrown
    expect(true)->toBeTrue();
});

test('can generate recovery codes with proper format', function () {
    $user = User::factory()->create();

    // Use reflection to test the protected method
    $reflection = new \ReflectionClass($user);
    $method     = $reflection->getMethod('generateRecoveryCode');
    $method->setAccessible(true);

    $code = $method->invoke($user);

    expect($code)->toBeString();
    expect(strlen($code))->toBe(8);
    // The code contains uppercase letters and numbers
    expect(strtoupper($code))->toBe($code); // Ensures it's uppercase
    expect(preg_match('/^[A-Z0-9]+$/', $code))->toBe(1);
});
