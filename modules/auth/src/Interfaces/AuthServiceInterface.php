<?php

declare(strict_types=1);

namespace Modules\Auth\Interfaces;

use Modules\Auth\DTO\LoginData;
use Modules\Auth\DTO\RegisterDTO;
use Modules\Auth\Models\User;

interface AuthServiceInterface
{
    public function login(LoginData $data): ?User;

    public function completeTwoFactorLogin(string $code): ?User;

    public function isTwoFactorRequired(): bool;

    public function register(RegisterDTO $data): User;

    public function logout(): void;

    public function sendPasswordResetLink(string $email): void;

    public function resetPassword(string $token, string $email, string $password): bool;

    public function verifyEmail(User $user): void;

    public function resendVerificationEmail(User $user): void;
}
