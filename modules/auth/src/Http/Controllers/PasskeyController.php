<?php

declare(strict_types=1);

namespace Modules\Auth\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Auth\Interfaces\PasskeyUser;
use Modules\Auth\Services\PasskeyService;
use Modules\Shared\Http\Controllers\BaseController;

class PasskeyController extends BaseController
{
    public function __construct(
        private readonly PasskeyService $passkeyService
    ) {}

    /**
     * Get user's passkeys.
     */
    public function index(): JsonResponse
    {
        $user     = Auth::user();
        $passkeys = $this->passkeyService->getUserPasskeys($user);

        return response()->json(['passkeys' => $passkeys]);
    }

    /**
     * Generate registration options for creating a new passkey.
     */
    public function registrationOptions(): JsonResponse
    {
        $user = Auth::user();

        if (! $user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        try {
            /** @var PasskeyUser $user */
            $options = $this->passkeyService->generateRegistrationOptions($user);

            return response()->json($options);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate registration options: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a new passkey.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name'                  => 'nullable|string|max:255',
            'credential'            => 'required|array',
            'credential.id'         => 'required|string',
            'credential.publicKey'  => 'required|string',
            'credential.signCount'  => 'integer|min:0',
            'credential.transports' => 'array',
        ]);

        $user = Auth::user();

        if (! $user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        try {
            /** @var PasskeyUser $user */
            $passkey = $this->passkeyService->storePasskey(
                $user,
                $request->input('credential'),
                $request->input('name')
            );

            return response()->json([
                'success' => true,
                'passkey' => [
                    'id'         => $passkey->id,
                    'name'       => $passkey->display_name,
                    'created_at' => $passkey->created_at->format('M j, Y'),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to register passkey: '.$e->getMessage(),
            ], 422);
        }
    }

    /**
     * Update passkey name.
     */
    public function update(Request $request, string $passkeyId): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $user = Auth::user();

        $success = $this->passkeyService->updatePasskeyName(
            $passkeyId,
            $request->input('name'),
            $user
        );

        if (! $success) {
            return response()->json([
                'success' => false,
                'message' => 'Passkey not found or update failed.',
            ], 404);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Delete a passkey.
     */
    public function destroy(string $passkeyId): JsonResponse
    {
        $user = Auth::user();

        $success = $this->passkeyService->deletePasskey($passkeyId, $user);

        if (! $success) {
            return response()->json([
                'success' => false,
                'message' => 'Passkey not found or deletion failed.',
            ], 404);
        }

        return response()->json(['success' => true]);
    }
}
