<?php

declare(strict_types=1);

namespace Modules\Auth\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Shared\Http\Controllers\BaseController;

class ConfirmablePasswordController extends BaseController
{
    /**
     * Show the confirm password view.
     */
    public function show(): Response
    {
        return Inertia::render('auth::confirm-password');
    }

    /**
     * Confirm the user's password.
     */
    public function store(Request $request): RedirectResponse
    {
        if (! Auth::guard('web')->validate([
            'email'    => $request->user()->email,
            'password' => $request->password,
        ])) {
            throw ValidationException::withMessages([
                'password' => trans('auth::messages.invalid_credentials'),
            ]);
        }

        $request->session()->put('auth.password_confirmed_at', time());

        return redirect()->intended(route('dashboard'));
    }

    /**
     * Check if the user's password has been confirmed recently.
     */
    public function status(): JsonResponse
    {
        $confirmedAt = session('auth.password_confirmed_at');

        // Password confirmation is valid for 3 hours (10800 seconds)
        $isConfirmed = $confirmedAt && (time() - $confirmedAt) < 10800;

        return response()->json(['confirmed' => $isConfirmed]);
    }
}
