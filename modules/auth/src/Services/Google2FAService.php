<?php

declare(strict_types=1);

namespace Modules\Auth\Services;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Facades\Crypt;
use Modules\Auth\Interfaces\UserRepositoryInterface;
use Modules\Auth\Models\User;
use Modules\Shared\Services\Base\Concretes\BaseService;
use PragmaRX\Google2FALaravel\Facade as Google2FA;
use Random\RandomException;

class Google2FAService extends BaseService
{
    public function __construct(
        protected UserRepositoryInterface $userRepository
    ) {
        $this->setRepository($this->userRepository);
    }

    /**
     * Get configured QR code writer.
     */
    private function getQRCodeWriter(): Writer
    {
        $renderer = new ImageRenderer(
            new RendererStyle(400),
            new SvgImageBackEnd
        );

        return new Writer($renderer);
    }

    /**
     * Generate a new secret key for two-factor authentication.
     */
    public function generateSecretKey(): string
    {
        return Google2FA::generateSecretKey();
    }

    /**
     * Generate QR code URL for Google Authenticator.
     */
    public function getQRCodeUrl(string $companyName, string $email, string $secretKey): string
    {
        // Generate the otpauth URL
        $otpUrl = sprintf(
            'otpauth://totp/%s:%s?secret=%s&issuer=%s',
            urlencode($companyName),
            urlencode($email),
            $secretKey,
            urlencode($companyName)
        );

        return $otpUrl;
    }

    /**
     * Generate QR code as SVG string.
     */
    public function getQRCodeSvg(string $companyName, string $email, string $secretKey): string
    {
        // Generate the otpauth URL
        $otpUrl = $this->getQRCodeUrl($companyName, $email, $secretKey);

        // Generate QR code SVG
        $writer = $this->getQRCodeWriter();

        return $writer->writeString($otpUrl);
    }

    /**
     * Verify a one-time password.
     */
    public function verifyOTP(string $secretKey, string $otp, int $window = 1): bool
    {
        return Google2FA::verifyKey($secretKey, $otp, $window);
    }

    /**
     * Enable two-factor authentication for a user.
     */
    public function enableTwoFactor(User $user): array
    {
        $secretKey     = $this->generateSecretKey();
        $recoveryCodes = $this->generateRecoveryCodes();

        // Store the secret key and recovery codes temporarily (not confirmed yet)
        $this->userRepository->updateByModel($user, [
            'two_factor_secret'         => Crypt::encrypt($secretKey),
            'two_factor_recovery_codes' => Crypt::encrypt(json_encode($recoveryCodes)),
        ]);

        return [
            'secret_key'     => $secretKey,
            'recovery_codes' => $recoveryCodes,
            'qr_code_url'    => $this->getQRCodeUrl(
                config('app.name'),
                $user->email,
                $secretKey
            ),
            'qr_code_svg' => $this->getQRCodeSvg(
                config('app.name'),
                $user->email,
                $secretKey
            ),
        ];
    }

    /**
     * Confirm two-factor authentication setup.
     */
    public function confirmTwoFactor(User $user, string $otp): bool
    {
        if (! $user->two_factor_secret) {
            return false;
        }

        $secretKey = Crypt::decrypt($user->two_factor_secret);

        if (! $this->verifyOTP($secretKey, $otp)) {
            return false;
        }

        // Generate recovery codes
        $recoveryCodes = $this->generateRecoveryCodes();

        $this->userRepository->updateByModel($user, [
            'two_factor_confirmed_at'   => now(),
            'two_factor_recovery_codes' => Crypt::encrypt(json_encode($recoveryCodes)),
        ]);

        return true;
    }

    /**
     * Disable two-factor authentication for a user.
     */
    public function disableTwoFactor(User $user): void
    {
        $this->userRepository->updateByModel($user, [
            'two_factor_secret'         => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at'   => null,
        ]);
    }

    /**
     * Check if user has two-factor authentication enabled.
     */
    public function hasTwoFactorEnabled(User $user): bool
    {
        return ! is_null($user->two_factor_confirmed_at) && ! is_null($user->two_factor_secret);
    }

    /**
     * Verify OTP or recovery code during login.
     */
    public function verifyTwoFactor(User $user, string $code): bool
    {
        // First try to verify as OTP
        if ($user->two_factor_secret) {
            $secretKey = Crypt::decrypt($user->two_factor_secret);
            if ($this->verifyOTP($secretKey, $code)) {
                return true;
            }
        }

        // If OTP fails, try recovery codes
        return $this->verifyRecoveryCode($user, $code);
    }

    /**
     * Verify a recovery code.
     */
    public function verifyRecoveryCode(User $user, string $code): bool
    {
        if (! $user->two_factor_recovery_codes) {
            return false;
        }

        $encryptedCodes = $user->two_factor_recovery_codes;
        $recoveryCodes  = json_decode(Crypt::decrypt($encryptedCodes), true);

        if (! in_array($code, $recoveryCodes)) {
            return false;
        }

        // Remove the used recovery code
        $recoveryCodes = array_diff($recoveryCodes, [$code]);

        $this->userRepository->updateByModel($user, [
            'two_factor_recovery_codes' => Crypt::encrypt(json_encode(array_values($recoveryCodes))),
        ]);

        return true;
    }

    /**
     * Get recovery codes for a user.
     */
    public function getRecoveryCodes(User $user): array
    {
        if (! $user->two_factor_recovery_codes) {
            return [];
        }

        $encryptedCodes = $user->two_factor_recovery_codes;

        return json_decode(Crypt::decrypt($encryptedCodes), true);
    }

    /**
     * Generate new recovery codes.
     */
    public function generateNewRecoveryCodes(User $user): array
    {
        $recoveryCodes = $this->generateRecoveryCodes();

        $this->userRepository->updateByModel($user, [
            'two_factor_recovery_codes' => Crypt::encrypt(json_encode($recoveryCodes)),
        ]);

        return $recoveryCodes;
    }

    /**
     * Generate recovery codes.
     */
    protected function generateRecoveryCodes(int $count = 8): array
    {
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            $codes[] = $this->generateRecoveryCode();
        }

        return $codes;
    }

    /**
     * @throws RandomException
     */
    protected function generateRecoveryCode(): string
    {
        // Generate more random bytes to ensure we have enough characters after filtering
        $code = '';
        while (strlen($code) < 8) {
            $randomBytes = random_bytes(12);
            $base64      = base64_encode($randomBytes);
            $filtered    = str_replace(['+', '/', '='], '', $base64);
            $code .= $filtered;
        }

        return strtoupper(substr($code, 0, 8));
    }

    /**
     * Get the two-factor secret for the user.
     */
    public function getTwoFactorSecret(User $user): string
    {
        if (! $user->two_factor_secret) {
            return '';
        }

        return Crypt::decrypt($user->two_factor_secret);
    }

    /**
     * Regenerate recovery codes for the user.
     */
    public function regenerateRecoveryCodes(User $user): array
    {
        return $this->generateNewRecoveryCodes($user);
    }
}
