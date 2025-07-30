import { authenticateWithPasskey, canUsePasskeys, formatAuthenticationOptions, getWebAuthnErrorMessage } from '@auth/lib/passkey-utils';
import { router } from '@inertiajs/react';
import axios from 'axios';
import { useCallback, useState } from 'react';
import { toast } from 'sonner';

interface UsePasskeyAuthReturn {
    isLoading: boolean;
    error: string | null;
    canUse: boolean;
    authenticateUser: (email?: string) => Promise<void>;
}

export function usePasskeyAuth(): UsePasskeyAuthReturn {
    const [isLoading, setIsLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const canUse = canUsePasskeys();

    const authenticateUser = useCallback(
        async (email?: string) => {
            if (!canUse) {
                toast.error('Passkeys are not supported in this browser or environment');
                return;
            }

            try {
                setIsLoading(true);
                setError(null);

                // Get authentication options from server
                const optionsResponse = await axios.post(route('passkey.auth.options'), email ? { email } : {});

                const options = optionsResponse.data;
                const formattedOptions = formatAuthenticationOptions(options);

                // Start WebAuthn authentication
                const credential = await authenticateWithPasskey(formattedOptions);

                // Send credential to server for verification
                const authResponse = await axios.post(route('passkey.auth.verify'), {
                    credential: {
                        id: credential.id,
                        rawId: credential.rawId,
                        response: {
                            authenticatorData: credential.response.authenticatorData,
                            clientDataJSON: credential.response.clientDataJSON,
                            signature: credential.response.signature,
                            userHandle: credential.response.userHandle,
                        },
                        type: credential.type,
                        signCount: 0, // Will be handled by server
                    },
                });

                const result = authResponse.data;

                if (result.success) {
                    toast.success('Successfully authenticated with passkey');

                    // Redirect to dashboard or intended page
                    if (result.redirect) {
                        router.visit(result.redirect);
                    } else {
                        router.visit(route('dashboard'));
                    }
                } else {
                    throw new Error(result.message || 'Authentication failed');
                }
            } catch (err) {
                const errorMessage = err instanceof Error ? getWebAuthnErrorMessage(err) : 'Authentication failed';
                setError(errorMessage);
                toast.error(errorMessage);
            } finally {
                setIsLoading(false);
            }
        },
        [canUse],
    );

    return {
        isLoading,
        error,
        canUse,
        authenticateUser,
    };
}
