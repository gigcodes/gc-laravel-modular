<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Models\User;
use Modules\Auth\Services\Google2FAService;
use PragmaRX\Google2FA\Google2FA;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service   = app(Google2FAService::class);
    $this->google2fa = new Google2FA;
});

test('can check if user has two factor enabled', function () {
    $userWithout2FA = User::factory()->create();
    $userWith2FA    = User::factory()->create([
        'two_factor_secret'       => encrypt($this->google2fa->generateSecretKey()),
        'two_factor_confirmed_at' => now(),
    ]);

    expect($this->service->hasTwoFactorEnabled($userWithout2FA))->toBeFalse();
    expect($this->service->hasTwoFactorEnabled($userWith2FA))->toBeTrue();
});

test('can generate secret key', function () {
    $secret = $this->service->generateSecretKey();

    expect($secret)->toBeString();
    expect(strlen($secret))->toBe(16);
});

test('can generate QR code URL', function () {
    $companyName = config('app.name');
    $email       = 'test@example.com';
    $secretKey   = 'JBSWY3DPEHPK3PXP';

    $url = $this->service->getQRCodeUrl($companyName, $email, $secretKey);

    expect($url)->toBeString();
    expect($url)->toContain('otpauth://totp/');
    expect($url)->toContain(urlencode($email));
    expect($url)->toContain(urlencode($companyName));
});

test('can get QR code SVG', function () {
    $companyName = config('app.name');
    $email       = 'test@example.com';
    $secretKey   = $this->google2fa->generateSecretKey();

    $svg = $this->service->getQRCodeSvg($companyName, $email, $secretKey);

    expect($svg)->toBeString();
    expect($svg)->toContain('<svg');
    expect($svg)->toContain('</svg>');
});

test('can verify two factor code', function () {
    $secret = $this->google2fa->generateSecretKey();
    $user   = User::factory()->create([
        'two_factor_secret' => encrypt($secret),
    ]);

    $validCode   = $this->google2fa->getCurrentOtp($secret);
    $invalidCode = '000000';

    expect($this->service->verifyTwoFactor($user, $validCode))->toBeTrue();
    expect($this->service->verifyTwoFactor($user, $invalidCode))->toBeFalse();
});

test('can verify recovery code', function () {
    $codes = ['CODE1-CODE1', 'CODE2-CODE2', 'CODE3-CODE3'];
    $user  = User::factory()->create([
        'two_factor_recovery_codes' => encrypt(json_encode($codes)),
    ]);

    expect($this->service->verifyRecoveryCode($user, 'CODE1-CODE1'))->toBeTrue();
    expect($this->service->verifyRecoveryCode($user, 'INVALID-CODE'))->toBeFalse();

    // Code should be removed after use
    $remainingCodes = json_decode(decrypt($user->fresh()->two_factor_recovery_codes), true);
    expect($remainingCodes)->not->toContain('CODE1-CODE1');
    expect($remainingCodes)->toHaveCount(2);
});

test('can enable two factor', function () {
    $user = User::factory()->create();

    $this->service->enableTwoFactor($user);

    $user->refresh();
    expect($user->two_factor_secret)->not->toBeNull();
    expect($user->two_factor_recovery_codes)->not->toBeNull();

    $recoveryCodes = json_decode(decrypt($user->two_factor_recovery_codes), true);
    expect($recoveryCodes)->toHaveCount(8);
});

test('can confirm two factor', function () {
    $secret = $this->google2fa->generateSecretKey();
    $user   = User::factory()->create([
        'two_factor_secret'       => encrypt($secret),
        'two_factor_confirmed_at' => null,
    ]);

    $otp    = $this->google2fa->getCurrentOtp($secret);
    $result = $this->service->confirmTwoFactor($user, $otp);

    expect($result)->toBeTrue();
    expect($user->fresh()->two_factor_confirmed_at)->not->toBeNull();
});

test('can disable two factor', function () {
    $user = User::factory()->create([
        'two_factor_secret'         => encrypt($this->google2fa->generateSecretKey()),
        'two_factor_confirmed_at'   => now(),
        'two_factor_recovery_codes' => encrypt(json_encode(['code1', 'code2'])),
    ]);

    $this->service->disableTwoFactor($user);

    $user->refresh();
    expect($user->two_factor_secret)->toBeNull();
    expect($user->two_factor_confirmed_at)->toBeNull();
    expect($user->two_factor_recovery_codes)->toBeNull();
});

test('can generate recovery codes', function () {
    // Since generateRecoveryCodes is protected, we test it indirectly through enableTwoFactor
    $user   = User::factory()->create();
    $result = $this->service->enableTwoFactor($user);

    expect($result['recovery_codes'])->toBeArray();
    expect($result['recovery_codes'])->toHaveCount(8);

    foreach ($result['recovery_codes'] as $code) {
        expect($code)->toBeString();
        expect(strlen($code))->toBe(8);
    }
});

test('can regenerate recovery codes', function () {
    $oldCodes = ['OLD1-OLD1', 'OLD2-OLD2'];
    $user     = User::factory()->create([
        'two_factor_recovery_codes' => encrypt(json_encode($oldCodes)),
    ]);

    $newCodes = $this->service->regenerateRecoveryCodes($user);

    expect($newCodes)->toHaveCount(8);
    expect($newCodes)->not->toContain('OLD1-OLD1');

    $storedCodes = json_decode(decrypt($user->fresh()->two_factor_recovery_codes), true);
    expect($storedCodes)->toEqual($newCodes);
});
