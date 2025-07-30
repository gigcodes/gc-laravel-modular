import { canUsePasskeys, formatRegistrationOptions, getWebAuthnErrorMessage, registerPasskey } from '@auth/lib/passkey-utils';
import axios from 'axios';
import { useCallback, useState } from 'react';
import { toast } from 'sonner';

interface Passkey {
    id: string;
    name: string;
    created_at: string;
    last_used_at?: string;
    transports?: string[];
}

interface UsePasskeysReturn {
    passkeys: Passkey[];
    isLoading: boolean;
    error: string | null;
    canUse: boolean;
    registerNewPasskey: (name?: string) => Promise<void>;
    deletePasskey: (passkeyId: string) => Promise<void>;
    updatePasskeyName: (passkeyId: string, name: string) => Promise<void>;
    refreshPasskeys: () => Promise<void>;
}

export function usePasskeys(): UsePasskeysReturn {
    const [passkeys, setPasskeys] = useState<Passkey[]>([]);
    const [isLoading, setIsLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const canUse = canUsePasskeys();

    const refreshPasskeys = useCallback(async () => {
        if (!canUse) return;

        try {
            setIsLoading(true);
            const response = await axios.get(route('passkeys.index'));

            if (response.status === 200) {
                setPasskeys(response.data.passkeys || []);
            } else {
                throw new Error('Failed to fetch passkeys');
            }
        } catch (err) {
            setError(err instanceof Error ? err.message : 'Failed to fetch passkeys');
        } finally {
            setIsLoading(false);
        }
    }, [canUse]);

    const registerNewPasskey = useCallback(
        async (name?: string) => {
            if (!canUse) {
                toast.error('Passkeys are not supported in this browser or environment');
                return;
            }

            try {
                setIsLoading(true);
                setError(null);

                if (process.env.NODE_ENV !== 'production') {
                    console.group('🔐 Starting Passkey Registration');
                    console.log('Name:', name);
                    console.log('Current URL:', window.location.href);
                    console.log('Registration options route:', route('passkeys.registration.options'));
                    console.log('Store route:', route('passkeys.store'));
                }

                // Get registration options from server
                const optionsResponse = await axios.post(route('passkeys.registration.options'));

                if (process.env.NODE_ENV !== 'production') {
                    console.log('Options response status:', optionsResponse.status);
                    console.log('Options response data:', optionsResponse.data);
                }

                const options = optionsResponse.data;
                if (process.env.NODE_ENV !== 'production') {
                    console.log('Registration options received:', options);
                }

                const formattedOptions = formatRegistrationOptions(options);
                if (process.env.NODE_ENV !== 'production') {
                    console.log('Formatted options:', formattedOptions);
                }

                if (process.env.NODE_ENV !== 'production') {
                    console.log('Starting WebAuthn registration...');
                }
                // Start WebAuthn registration
                const credential = await registerPasskey(formattedOptions);
                if (process.env.NODE_ENV !== 'production') {
                    console.log('WebAuthn credential received:', credential);
                }

                // Send credential to server
                const registerResponse = await axios.post(route('passkeys.store'), {
                    name,
                    credential: {
                        id: credential.id,
                        publicKey: credential.response.publicKey,
                        signCount: credential.response.authenticatorData ? 0 : undefined,
                        transports: credential.response.transports,
                    },
                });

                if (process.env.NODE_ENV !== 'production') {
                    console.log('Store response status:', registerResponse.status);
                    console.log('Store response data:', registerResponse.data);
                }

                const result = registerResponse.data;
                if (process.env.NODE_ENV !== 'production') {
                    console.log('Store response result:', result);
                }

                if (result.success) {
                    if (process.env.NODE_ENV !== 'production') {
                        console.log('Passkey registered successfully!');
                    }
                    toast.success('Passkey registered successfully');
                    await refreshPasskeys();
                } else {
                    throw new Error(result.message || 'Failed to register passkey');
                }
            } catch (err) {
                if (process.env.NODE_ENV !== 'production') {
                    console.error('Passkey registration error:', err);
                }
                const errorMessage = err instanceof Error ? getWebAuthnErrorMessage(err) : 'Failed to register passkey';
                setError(errorMessage);
                toast.error(errorMessage);
            } finally {
                setIsLoading(false);
                if (process.env.NODE_ENV !== 'production') {
                    console.groupEnd();
                }
            }
        },
        [canUse, refreshPasskeys],
    );

    const deletePasskey = useCallback(
        async (passkeyId: string) => {
            try {
                setIsLoading(true);

                const response = await axios.delete(route('passkeys.destroy', passkeyId));

                const result = response.data;

                if (result.success) {
                    toast.success('Passkey deleted successfully');
                    await refreshPasskeys();
                } else {
                    throw new Error(result.message || 'Failed to delete passkey');
                }
            } catch (err) {
                const errorMessage = err instanceof Error ? err.message : 'Failed to delete passkey';
                setError(errorMessage);
                toast.error(errorMessage);
            } finally {
                setIsLoading(false);
            }
        },
        [refreshPasskeys],
    );

    const updatePasskeyName = useCallback(
        async (passkeyId: string, name: string) => {
            try {
                setIsLoading(true);

                const response = await axios.put(route('passkeys.update', passkeyId), { name });

                const result = response.data;

                if (result.success) {
                    toast.success('Passkey name updated successfully');
                    await refreshPasskeys();
                } else {
                    throw new Error(result.message || 'Failed to update passkey name');
                }
            } catch (err) {
                const errorMessage = err instanceof Error ? err.message : 'Failed to update passkey name';
                setError(errorMessage);
                toast.error(errorMessage);
            } finally {
                setIsLoading(false);
            }
        },
        [refreshPasskeys],
    );

    return {
        passkeys,
        isLoading,
        error,
        canUse,
        registerNewPasskey,
        deletePasskey,
        updatePasskeyName,
        refreshPasskeys,
    };
}
