<?php

declare(strict_types=1);

namespace Modules\Auth\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Auth\DTO\RegisterDTO;
use Modules\Auth\Interfaces\AuthServiceInterface;
use Modules\Shared\Http\Controllers\BaseController;

class RegisterController extends BaseController
{
    public function __construct(
        private readonly AuthServiceInterface $authService,
    ) {}

    /**
     * Display the registration view.
     */
    public function create(): Response
    {
        return Inertia::render('auth::register');
    }

    /**
     * Handle an incoming registration request.
     */
    public function store(RegisterDTO $dto): RedirectResponse
    {
        $user = $this->authService->register($dto);

        Auth::login($user);

        return redirect(route('dashboard'));
    }
}
