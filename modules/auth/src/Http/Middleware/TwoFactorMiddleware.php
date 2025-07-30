<?php

declare(strict_types=1);

namespace Modules\Auth\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Auth\Services\Google2FAService;
use Symfony\Component\HttpFoundation\Response;

class TwoFactorMiddleware
{
    public function __construct(
        private readonly Google2FAService $google2FAService
    ) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        // If user is not authenticated, let the auth middleware handle it
        if (! $user) {
            return $next($request);
        }

        // If user doesn't have two-factor enabled, proceed normally
        if (! $this->google2FAService->hasTwoFactorEnabled($user)) {
            return $next($request);
        }

        // If two-factor authentication is already verified in this session, proceed
        if (session('auth.two_factor_verified') === $user->id) {
            return $next($request);
        }

        // Store the user ID and mark two-factor as required
        session([
            'auth.two_factor_required' => true,
            'auth.two_factor_user_id'  => $user->id,
        ]);

        // Log out the user temporarily
        Auth::logout();

        // Redirect to two-factor challenge
        if ($request->expectsJson()) {
            return response()->json([
                'message'             => 'Two-factor authentication required.',
                'two_factor_required' => true,
            ], 423);
        }

        return redirect()->guest(route('two-factor.challenge'));
    }
}
