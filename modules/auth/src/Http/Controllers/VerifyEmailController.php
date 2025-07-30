<?php

namespace Modules\Auth\Http\Controllers;

use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Modules\Auth\Interfaces\AuthServiceInterface;
use Modules\Shared\Http\Controllers\BaseController;

class VerifyEmailController extends BaseController
{
    public function __construct(
        private readonly AuthServiceInterface $authService,
    ) {}

    public function __invoke(EmailVerificationRequest $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(route('dashboard').'?verified=1');
        }

        $this->authService->verifyEmail($request->user());

        return redirect()->intended(route('dashboard').'?verified=1');
    }
}
