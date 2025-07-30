<?php

declare(strict_types=1);

namespace Modules\Auth\Services;

use Modules\Auth\Interfaces\PasskeyUser;
use Modules\Auth\Models\Passkey;
use Modules\Auth\Models\User;
use Modules\Shared\Services\Base\Concretes\BaseService;
use Random\RandomException;

class PasskeyService extends BaseService
{
    /**
     * Generate registration options for a user.
     *
     * @throws RandomException
     */
    public function generateRegistrationOptions(PasskeyUser $user): array
    {
        $challenge = base64_encode(random_bytes(32));

        // Store challenge in session for verification
        session(['passkey_challenge' => $challenge]);

        return [
            'challenge' => $challenge,
            'rp'        => [
                'name' => config('app.name'),
                'id'   => parse_url(config('app.url'), PHP_URL_HOST),
            ],
            'user' => [
                'id'          => base64_encode((string) $user->getPasskeyUserId()),
                'name'        => $user->getPasskeyUsername(),
                'displayName' => $user->getPasskeyDisplayName(),
            ],
            'pubKeyCredParams' => [
                ['alg' => -7, 'type' => 'public-key'], // ES256
                ['alg' => -257, 'type' => 'public-key'], // RS256
            ],
            'authenticatorSelection' => [
                'authenticatorAttachment' => 'platform',
                'userVerification'        => 'preferred',
                'residentKey'             => 'preferred',
            ],
            'timeout'            => 60000,
            'excludeCredentials' => $this->getExcludeCredentials($user),
        ];
    }

    /**
     * Generate authentication options for a user.
     */
    public function generateAuthenticationOptions(?PasskeyUser $user = null): array
    {
        $challenge = base64_encode(random_bytes(32));

        // Store challenge in session for verification
        session(['passkey_challenge' => $challenge]);

        $allowCredentials = [];
        if ($user && $user->hasPasskeys()) {
            $allowCredentials = $this->getAllowCredentials($user);
        }

        return [
            'challenge'        => $challenge,
            'timeout'          => 60000,
            'userVerification' => 'preferred',
            'allowCredentials' => $allowCredentials,
        ];
    }

    /**
     * Store a new passkey for a user.
     */
    public function storePasskey(PasskeyUser $user, array $credentialData, ?string $name = null): Passkey
    {
        return $user->createPasskey([
            'name'             => $name,
            'credential_id'    => $credentialData['id'],
            'public_key'       => $credentialData['publicKey'],
            'sign_count'       => $credentialData['signCount'] ?? 0,
            'aaguid'           => $credentialData['aaguid'] ?? null,
            'transports'       => $credentialData['transports'] ?? [],
            'type'             => 'public-key',
            'attestation_data' => $credentialData,
        ]);
    }

    /**
     * Verify passkey authentication.
     */
    public function verifyAuthentication(array $credentialData): ?User
    {
        $credentialId = $credentialData['id'];
        $passkey      = Passkey::byCredentialId($credentialId)->first();

        if (! $passkey) {
            return null;
        }

        // Update usage statistics
        $passkey->updateUsage($credentialData['signCount'] ?? $passkey->sign_count + 1);

        return $passkey->user;
    }

    /**
     * Delete a passkey by ID.
     */
    public function deletePasskey(string $passkeyId, PasskeyUser $user): bool
    {
        return $user->deletePasskey($passkeyId);
    }

    /**
     * Get user's passkeys for display.
     */
    public function getUserPasskeys(PasskeyUser $user): array
    {
        return $user->passkeys()->get()->map(function (Passkey $passkey) {
            return [
                'id'           => $passkey->id,
                'name'         => $passkey->display_name,
                'created_at'   => $passkey->created_at->format('M j, Y'),
                'last_used_at' => $passkey->last_used_at?->format('M j, Y g:i A'),
                'transports'   => $passkey->transports,
            ];
        })->toArray();
    }

    /**
     * Update passkey name.
     */
    public function updatePasskeyName(string $passkeyId, string $name, PasskeyUser $user): bool
    {
        $passkey = $user->passkeys()->find($passkeyId);

        if (! $passkey) {
            return false;
        }

        $passkey->update(['name' => $name]);

        return true;
    }

    /**
     * Get credentials to exclude during registration.
     */
    private function getExcludeCredentials(PasskeyUser $user): array
    {
        return $user->passkeys()->get()->map(function (Passkey $passkey) {
            return [
                'id'         => $passkey->credential_id,
                'type'       => 'public-key',
                'transports' => $passkey->transports ?: [],
            ];
        })->toArray();
    }

    /**
     * Get allowed credentials for authentication.
     */
    private function getAllowCredentials(PasskeyUser $user): array
    {
        return $user->passkeys()->get()->map(function (Passkey $passkey) {
            return [
                'id'         => $passkey->credential_id,
                'type'       => 'public-key',
                'transports' => $passkey->transports ?: [],
            ];
        })->toArray();
    }
}
