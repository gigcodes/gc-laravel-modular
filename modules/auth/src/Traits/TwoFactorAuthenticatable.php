<?php

namespace Modules\Auth\Traits;

use Illuminate\Support\Facades\Crypt;
use Modules\Auth\Services\Google2FAService;

trait TwoFactorAuthenticatable
{
    /**
     * Get the user's two factor authentication recovery codes.
     */
    public function recoveryCodes(): array
    {
        return app(Google2FAService::class)->getRecoveryCodes($this);
    }

    /**
     * Replace the given recovery code with a new one in the user's stored codes.
     */
    public function replaceRecoveryCode(string $code): void
    {
        app(Google2FAService::class)->verifyRecoveryCode($this, $code);
    }

    /**
     * Get the QR code SVG of the user's two factor authentication QR code URL.
     */
    public function twoFactorQrCodeSvg(): string
    {
        if (! $this->two_factor_secret) {
            return '';
        }

        $secretKey = Crypt::decrypt($this->two_factor_secret);

        return app(Google2FAService::class)->getQRCodeSvg(
            config('app.name'),
            $this->email,
            $secretKey
        );
    }

    /**
     * Get the two factor authentication QR code URL.
     */
    public function twoFactorQrCodeUrl(): string
    {
        if (! $this->two_factor_secret) {
            return '';
        }

        $secretKey = Crypt::decrypt($this->two_factor_secret);

        return app(Google2FAService::class)->getQRCodeUrl(
            config('app.name'),
            $this->email,
            $secretKey
        );
    }

    /**
     * Determine if two-factor authentication has been enabled.
     */
    public function hasEnabledTwoFactorAuthentication(): bool
    {
        return app(Google2FAService::class)->hasTwoFactorEnabled($this);
    }

    /**
     * Determine if two-factor authentication has been confirmed.
     */
    public function twoFactorAuthenticationConfirmed(): bool
    {
        return ! is_null($this->two_factor_confirmed_at);
    }

    /**
     * Get the decrypted two-factor secret.
     */
    public function getDecryptedTwoFactorSecret(): ?string
    {
        if (! $this->two_factor_secret) {
            return null;
        }

        return Crypt::decrypt($this->two_factor_secret);
    }

    /**
     * Generate a new recovery code.
     */
    protected function generateRecoveryCode(): string
    {
        return strtoupper(substr(str_replace(['+', '/', '='], '', base64_encode(random_bytes(6))), 0, 8));
    }
}
