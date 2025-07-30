<?php

declare(strict_types=1);

namespace Modules\Auth\Interfaces;

use Modules\Auth\Models\Passkey;

interface PasskeyUser
{
    /**
     * Get all passkeys for the user.
     */
    public function passkeys();

    /**
     * Check if the user has any passkeys.
     */
    public function hasPasskeys(): bool;

    /**
     * Get the username for WebAuthn.
     */
    public function getPasskeyUsername(): string;

    /**
     * Get the user ID for WebAuthn.
     */
    public function getPasskeyUserId(): int;

    /**
     * Get the display name for WebAuthn.
     */
    public function getPasskeyDisplayName(): string;

    /**
     * Get the user icon URL for WebAuthn.
     */
    public function getPasskeyUserIcon(): ?string;

    /**
     * Find a passkey by credential ID.
     */
    public function findPasskeyByCredentialId(string $credentialId): ?Passkey;

    /**
     * Create a new passkey for the user.
     */
    public function createPasskey(array $data): Passkey;

    /**
     * Delete a passkey by ID.
     */
    public function deletePasskey(string $passkeyId): bool;

    /**
     * Get passkey credentials for authentication.
     */
    public function getPasskeyCredentials(): array;
}
