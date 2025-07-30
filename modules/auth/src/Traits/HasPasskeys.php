<?php

declare(strict_types=1);

namespace Modules\Auth\Traits;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Auth\Models\Passkey;

trait HasPasskeys
{
    /**
     * Get all passkeys for the user.
     */
    public function passkeys(): HasMany
    {
        return $this->hasMany(Passkey::class)->orderBy('created_at', 'desc');
    }

    /**
     * Check if the user has any passkeys.
     */
    public function hasPasskeys(): bool
    {
        return $this->passkeys()->exists();
    }

    /**
     * Get the username for WebAuthn (typically email).
     */
    public function getPasskeyUsername(): string
    {
        return $this->email;
    }

    /**
     * Get the user ID for WebAuthn.
     */
    public function getPasskeyUserId(): int
    {
        return $this->id;
    }

    /**
     * Get the display name for WebAuthn.
     */
    public function getPasskeyDisplayName(): string
    {
        return $this->name;
    }

    /**
     * Get the user icon URL for WebAuthn (optional).
     */
    public function getPasskeyUserIcon(): ?string
    {
        return $this->profile_photo_url ?? null;
    }

    /**
     * Find a passkey by credential ID.
     */
    public function findPasskeyByCredentialId(string $credentialId): ?Passkey
    {
        return $this->passkeys()->byCredentialId($credentialId)->first();
    }

    /**
     * Create a new passkey for the user.
     */
    public function createPasskey(array $data): Passkey
    {
        return $this->passkeys()->create($data);
    }

    /**
     * Delete a passkey by ID.
     */
    public function deletePasskey(string $passkeyId): bool
    {
        return $this->passkeys()->where('id', $passkeyId)->delete() > 0;
    }

    /**
     * Get passkey credentials for authentication.
     */
    public function getPasskeyCredentials(): array
    {
        return $this->passkeys()
            ->select(['credential_id', 'public_key', 'sign_count'])
            ->get()
            ->map(function (Passkey $passkey) {
                return [
                    'id'        => $passkey->credential_id,
                    'publicKey' => $passkey->public_key,
                    'signCount' => $passkey->sign_count,
                ];
            })
            ->toArray();
    }
}
