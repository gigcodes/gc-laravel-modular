<?php

declare(strict_types=1);

namespace Modules\Auth\Http\Controllers;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Auth\Models\User;
use Modules\Auth\Services\PasskeyService;
use Modules\Shared\Http\Controllers\BaseController;

class PasskeyAuthController extends BaseController
{
    public function __construct(
        private readonly PasskeyService $passkeyService
    ) {}

    /**
     * Generate authentication options for passkey login.
     */
    public function authenticationOptions(Request $request): JsonResponse
    {
        $user = null;

        // If email is provided, find the user to get their specific passkeys
        if ($request->has('email')) {
            $user = User::where('email', $request->input('email'))->first();
        }

        $options = $this->passkeyService->generateAuthenticationOptions($user);

        return response()->json($options);
    }

    /**
     * Verify passkey authentication and log in the user.
     */
    public function authenticate(Request $request): JsonResponse|RedirectResponse
    {
        $request->validate([
            'credential'           => 'required|array',
            'credential.id'        => 'required|string',
            'credential.signCount' => 'integer|min:0',
        ]);

        try {
            $user = $this->passkeyService->verifyAuthentication(
                $request->input('credential')
            );

            if (! $user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid passkey or user not found.',
                ], 401);
            }

            // Log in the user
            Auth::login($user, true); // Remember the user
            $request->session()->regenerate();

            if ($request->expectsJson()) {
                return response()->json([
                    'success'  => true,
                    'redirect' => route('dashboard'),
                ]);
            }

            return redirect()->intended(route('dashboard'));
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication failed: '.$e->getMessage(),
            ], 422);
        }
    }

    /**
     * Check if a user has passkeys available.
     */
    public function checkUserPasskeys(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $user = User::where('email', $request->input('email'))->first();

        if (! $user) {
            return response()->json([
                'hasPasskeys' => false,
                'message'     => 'User not found.',
            ], 404);
        }

        return response()->json([
            'hasPasskeys'  => $user->hasPasskeys(),
            'passkeyCount' => $user->passkeys()->count(),
        ]);
    }
}
