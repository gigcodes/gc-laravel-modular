<?php

declare(strict_types=1);

namespace Modules\Auth\Services;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Modules\Auth\DTO\LoginData;
use Modules\Auth\DTO\RegisterDTO;
use Modules\Auth\Interfaces\AuthServiceInterface;
use Modules\Auth\Interfaces\UserRepositoryInterface;
use Modules\Auth\Models\User;
use Modules\Shared\Services\Base\Concretes\BaseService;
use Throwable;

class AuthService extends BaseService implements AuthServiceInterface
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly Google2FAService $google2FAService,
    ) {
        $this->setRepository($this->userRepository);
    }

    public function login(LoginData $data): ?User
    {
        $credentials = [
            'email'    => $data->email,
            'password' => $data->password,
        ];

        if (Auth::attempt($credentials, $data->remember)) {
            $user = Auth::user();

            // Check if user has two-factor authentication enabled
            if ($this->google2FAService->hasTwoFactorEnabled($user)) {
                // Store authentication state for two-factor verification
                session([
                    'auth.two_factor_required' => true,
                    'auth.two_factor_user_id'  => $user->id,
                    'auth.remember'            => $data->remember,
                ]);

                // Log out the user temporarily until two-factor is verified
                Auth::logout();

                // Return user but with two-factor pending
                return $user;
            }

            // Mark two-factor as verified for this session (for users without 2FA)
            session(['auth.two_factor_verified' => $user->id]);

            return $user;
        }

        return null;
    }

    /**
     * Complete two-factor authentication and log in the user.
     */
    public function completeTwoFactorLogin(string $code): ?User
    {
        if (! session('auth.two_factor_required') || ! session('auth.two_factor_user_id')) {
            return null;
        }

        $user = $this->userRepository->find(session('auth.two_factor_user_id'));

        if (! $user) {
            return null;
        }

        if (! $this->google2FAService->verifyTwoFactor($user, $code)) {
            return null;
        }

        // Clear two-factor session data
        session()->forget(['auth.two_factor_required', 'auth.two_factor_user_id']);

        // Log the user in
        Auth::login($user, session('auth.remember', false));
        session()->forget('auth.remember');

        // Mark two-factor as verified for this session
        session(['auth.two_factor_verified' => $user->id]);

        return $user;
    }

    /**
     * Check if two-factor authentication is required.
     */
    public function isTwoFactorRequired(): bool
    {
        return session('auth.two_factor_required', false);
    }

    /**
     * @throws Throwable
     */
    public function register(RegisterDTO $data): User
    {
        return DB::transaction(function () use ($data) {
            $user = $this->userRepository->create([
                'name'     => $data->name,
                'email'    => $data->email,
                'password' => Hash::make($data->password),
            ]);

            event(new Registered($user));

            return $user;
        });
    }

    /**
     * Log out the current user.
     */
    public function logout(): void
    {
        // Clear two-factor session data
        session()->forget([
            'auth.two_factor_verified',
            'auth.two_factor_required',
            'auth.two_factor_user_id',
            'auth.remember',
        ]);

        Auth::logout();
    }

    /**
     * Send password reset link.
     */
    public function sendPasswordResetLink(string $email): void
    {
        Password::sendResetLink(['email' => $email]);
    }

    /**
     * Reset user password.
     */
    public function resetPassword(string $token, string $email, string $password): bool
    {
        $status = Password::reset(
            [
                'email'                 => $email,
                'password'              => $password,
                'password_confirmation' => $password,
                'token'                 => $token,
            ],
            function ($user) use ($password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->save();
            }
        );

        return $status === Password::PASSWORD_RESET;
    }

    /**
     * Verify user email.
     */
    public function verifyEmail(User $user): void
    {
        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
            event(new Verified($user));
        }
    }

    /**
     * Resend email verification.
     */
    public function resendVerificationEmail(User $user): void
    {
        $user->sendEmailVerificationNotification();
    }
}
