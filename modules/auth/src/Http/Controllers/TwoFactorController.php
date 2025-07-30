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
use Modules\Auth\Events\UserLoggedIn;
use Modules\Auth\Models\User;
use Modules\Auth\Services\Google2FAService;
use Modules\Shared\Http\Controllers\BaseController;

class TwoFactorController extends BaseController
{
    public function __construct(
        private readonly Google2FAService $google2FAService
    ) {}

    /**
     * Enable two-factor authentication for the user.
     */
    public function enable(Request $request): RedirectResponse
    {
        $user = Auth::user();

        if ($this->google2FAService->hasTwoFactorEnabled($user)) {
            return back()->withErrors(['two_factor' => 'Two-factor authentication is already enabled.']);
        }

        $this->google2FAService->enableTwoFactor($user);

        return back()->with('status', 'two-factor-authentication-enabled');
    }

    /**
     * Get the QR code for two-factor authentication.
     */
    public function qrCode(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (! $user->two_factor_secret) {
            return response()->json(['error' => 'Two-factor authentication is not set up.'], 404);
        }

        // Return only secret without checking confirmation
        $secret = $this->google2FAService->getTwoFactorSecret($user);

        $qrCodeSvg = $this->google2FAService->getQRCodeSvg(
            config('app.name'),
            $user->email,
            $secret
        );

        return response()->json(['svg' => $qrCodeSvg]);
    }

    /**
     * Get the secret key for two-factor authentication.
     */
    public function secretKey(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (! $user->two_factor_secret) {
            return response()->json(['error' => 'Two-factor authentication is not set up.'], 400);
        }

        return response()->json([
            'secretKey' => $this->google2FAService->getTwoFactorSecret($user),
        ]);
    }

    /**
     * Get the recovery codes for two-factor authentication.
     */
    public function recoveryCodes(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (! $user->two_factor_recovery_codes) {
            return response()->json(['error' => 'Recovery codes not available.'], 404);
        }

        return response()->json($this->google2FAService->getRecoveryCodes($user));
    }

    /**
     * Regenerate the recovery codes for two-factor authentication.
     */
    public function regenerateRecoveryCodes(Request $request): RedirectResponse
    {
        // Check if password has been confirmed recently (within last 3 hours)
        $confirmedAt = session('auth.password_confirmed_at');
        if (! $confirmedAt || (time() - $confirmedAt) >= 10800) {
            return back()->withErrors(['password' => 'Password confirmation required.']);
        }

        $user = Auth::user();

        if (! $this->google2FAService->hasTwoFactorEnabled($user)) {
            return back()->withErrors(['two_factor' => 'Two-factor authentication is not fully enabled.']);
        }

        $this->google2FAService->regenerateRecoveryCodes($user);

        return back()->with('status', 'recovery-codes-regenerated');
    }

    /**
     * Confirm two-factor authentication setup.
     */
    public function confirm(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => ['required', 'string', 'size:6'],
        ]);

        $user = Auth::user();

        if (! $user->two_factor_secret) {
            abort(404);
        }

        if ($this->google2FAService->hasTwoFactorEnabled($user)) {
            return back()->withErrors(['code' => 'Two-factor authentication is already confirmed.']);
        }

        $confirmed = $this->google2FAService->confirmTwoFactor($user, $request->code);

        if (! $confirmed) {
            return back()->withErrors(['code' => 'The provided two-factor authentication code was invalid.']);
        }

        return back()->with('status', 'two-factor-authentication-confirmed');
    }

    /**
     * Disable two-factor authentication for the user.
     */
    public function disable(Request $request): RedirectResponse
    {
        // Check if password has been confirmed recently (within last 3 hours)
        $confirmedAt = session('auth.password_confirmed_at');
        if (! $confirmedAt || (time() - $confirmedAt) >= 10800) {
            return back()->withErrors(['password' => 'Password confirmation required.']);
        }

        $user = Auth::user();

        $this->google2FAService->disableTwoFactor($user);

        return back()->with('status', 'two-factor-authentication-disabled');
    }

    /**
     * Show the two-factor challenge page.
     */
    public function challenge(): Response|RedirectResponse
    {
        //        if (! session('login.id')) {
        //            return redirect()->route('dashboard');
        //        }

        return Inertia::render('auth::two-factor/challenge');
    }

    /**
     * Verify the two-factor authentication code.
     */
    public function verify(Request $request): RedirectResponse|JsonResponse
    {
        $hasCode         = $request->filled('code');
        $hasRecoveryCode = $request->filled('recovery_code');

        if (! $hasCode && ! $hasRecoveryCode) {
            throw ValidationException::withMessages([
                'code' => ['A code or recovery code is required.'],
            ]);
        }

        if (! session('auth.two_factor_user_id')) {
            throw ValidationException::withMessages([
                'code' => ['Two-factor authentication is not required.'],
            ]);
        }

        $user = User::find(session('auth.two_factor_user_id'));

        if (! $user) {
            throw ValidationException::withMessages([
                'code' => ['User not found.'],
            ]);
        }

        $verified = false;

        if ($hasRecoveryCode) {
            $verified = $this->google2FAService->verifyRecoveryCode($user, $request->recovery_code);
            if (! $verified) {
                throw ValidationException::withMessages([
                    'recovery_code' => ['The provided recovery code was invalid.'],
                ]);
            }
        } else {
            $verified = $this->google2FAService->verifyTwoFactor($user, $request->code);
            if (! $verified) {
                throw ValidationException::withMessages([
                    'code' => ['The provided two-factor authentication code was invalid.'],
                ]);
            }
        }

        // Clear two-factor session data
        session()->forget(['auth.two_factor_user_id', 'auth.two_factor_required']);

        // Log the user in
        Auth::login($user, session('auth.remember', false));
        session()->forget('auth.remember');

        // Mark two-factor as verified for this session
        session(['auth.two_factor_verified' => $user->id]);

        $request->session()->regenerate();

        // Fire login event
        event(new UserLoggedIn($user));

        if ($request->expectsJson()) {
            return response()->json([
                'success'  => true,
                'redirect' => route('dashboard'),
            ]);
        }

        return redirect()->intended(route('dashboard'));
    }
}
