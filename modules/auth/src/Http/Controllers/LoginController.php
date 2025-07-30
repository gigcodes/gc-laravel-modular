<?php

declare(strict_types=1);

namespace Modules\Auth\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Auth\DTO\LoginData;
use Modules\Auth\Events\UserLoggedIn;
use Modules\Auth\Interfaces\AuthServiceInterface;
use Modules\Auth\Models\User;
use Modules\Shared\Http\Controllers\BaseController;

class LoginController extends BaseController
{
    public function __construct(
        private readonly AuthServiceInterface $authService,
    ) {}

    /**
     * Display the login view.
     */
    public function create(): Response
    {
        return Inertia::render('auth::login', [
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginData $dto): RedirectResponse
    {
        $user = $this->authService->login($dto);

        if (! $user) {
            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        // Check if two-factor authentication is required
        if ($this->authService->isTwoFactorRequired()) {
            return redirect()->route('two-factor.challenge');
        }

        session()->regenerate();

        event(new UserLoggedIn($user));

        return redirect()->intended(route('dashboard'));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $this->authService->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    /**
     * Check user status including email verification and passkey availability.
     */
    public function checkUserStatus(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $user = User::where('email', $request->input('email'))->first();

        if (! $user) {
            return response()->json([
                'userExists'           => false,
                'requiresVerification' => false,
                'hasPasskeys'          => false,
                'passkeyCount'         => 0,
            ]);
        }

        return response()->json([
            'userExists'           => true,
            'requiresVerification' => ! $user->hasVerifiedEmail(),
            'hasPasskeys'          => $user->hasPasskeys(),
            'passkeyCount'         => $user->passkeys()->count(),
        ]);
    }
}
