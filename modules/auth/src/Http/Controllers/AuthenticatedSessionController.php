<?php

declare(strict_types=1);

namespace Modules\Auth\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Auth\DTO\LoginData;
use Modules\Auth\Interfaces\AuthServiceInterface;
use Modules\Shared\Http\Controllers\BaseController;

class AuthenticatedSessionController extends BaseController
{
    public function __construct(
        private readonly AuthServiceInterface $authService
    ) {}

    /**
     * Show the login page.
     */
    public function create(Request $request): Response
    {
        return Inertia::render('auth::login', [
            'canResetPassword' => Route::has('password.request'),
            'status'           => $request->session()->get('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginData $data, Request $request): RedirectResponse
    {
        $this->authService->authenticate($data, $request->ip());

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $this->authService->logout();

        return redirect('/');
    }
}
