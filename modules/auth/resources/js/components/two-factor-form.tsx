import { useState, useMemo, useEffect } from 'react';
import { router, usePage } from '@inertiajs/react';
import axios from 'axios';
import { RefreshCw, Eye, EyeOff } from 'lucide-react';
import { Button } from '@shared/components/ui/button';
import { Input } from '@shared/components/ui/input';
import HeadingSmall from '@shared/components/heading-small';
import { Label } from '@shared/components/ui/label';
import { Alert, AlertDescription } from '@shared/components/ui/alert';
import { Separator } from '@shared/components/ui/separator';
import type { User } from '@shared/types';

export function TwoFactorForm() {
    const { auth, confirmsTwoFactorAuthentication } = usePage().props as unknown as {
        auth: { user: User & { two_factor_enabled?: boolean; two_factor_confirmed_at?: string | null } };
        confirmsTwoFactorAuthentication?: boolean;
    };

    const [enabling, setEnabling] = useState(false);
    const [confirming, setConfirming] = useState(confirmsTwoFactorAuthentication || false);
    const [disabling, setDisabling] = useState(false);
    const [qrCode, setQrCode] = useState<string | null>(null);
    const [setupKey, setSetupKey] = useState<string | null>(null);
    const [recoveryCodes, setRecoveryCodes] = useState<string[]>([]);
    const [showingRecoveryCodes, setShowingRecoveryCodes] = useState(false);
    const [confirmationCode, setConfirmationCode] = useState('');
    const [error, setError] = useState<string | null>(null);

    const twoFactorEnabled = useMemo(
        () => !enabling && auth.user?.two_factor_enabled,
        [enabling, auth.user]
    );

    // If we're in confirming state initially (from server), load QR code
    useEffect(() => {
        if (confirmsTwoFactorAuthentication && !qrCode) {
            Promise.all([showQrCode(), showSetupKey()]);
        }
    }, [confirmsTwoFactorAuthentication, qrCode]);

    const showQrCode = async () => {
        try {
            const response = await axios.get(route('two-factor.qr-code'));
            setQrCode(response.data.svg);
        } catch (error) {
            console.error('Failed to fetch QR code:', error);
        }
    };

    const showSetupKey = async () => {
        try {
            const response = await axios.get(route('two-factor.secret-key'));
            setSetupKey(response.data.secretKey);
        } catch (error) {
            console.error('Failed to fetch setup key:', error);
        }
    };

    const toggleRecoveryCodes = async () => {
        if (showingRecoveryCodes) {
            setShowingRecoveryCodes(false);
            setRecoveryCodes([]);
        } else {
            try {
                const response = await axios.get(route('two-factor.recovery-codes'));
                setRecoveryCodes(response.data);
                setShowingRecoveryCodes(true);
            } catch (error) {
                console.error('Failed to fetch recovery codes:', error);
            }
        }
    };

    const enableTwoFactorAuthentication = async () => {
        setEnabling(true);
        setError(null);

        try {
            await axios.post(route('two-factor.enable'));
            await Promise.all([showQrCode(), showSetupKey()]);
            setConfirming(true);
        } catch (error: unknown) {
            console.error('Two-factor enable error:', error);
            let message = 'Failed to enable two-factor authentication';
            if (error && typeof error === 'object' && 'response' in error) {
                const axiosError = error as { response?: { data?: { message?: string; errors?: { two_factor?: string } } } };
                message = axiosError.response?.data?.message || axiosError.response?.data?.errors?.two_factor || message;
            }
            setError(message);
        } finally {
            setEnabling(false);
        }
    };

    const confirmTwoFactorAuthentication = (e: React.FormEvent) => {
        e.preventDefault();
        setError(null);

        router.post(route('two-factor.confirm'), {
            code: confirmationCode
        }, {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                setConfirming(false);
                setQrCode(null);
                setSetupKey(null);
                setConfirmationCode('');
            },
            onError: (errors) => {
                setError(errors.code || 'Invalid confirmation code');
            }
        });
    };

    const regenerateRecoveryCodes = async () => {
        try {
            await axios.post(route('two-factor.recovery-codes.regenerate'));
            if (showingRecoveryCodes) {
                const response = await axios.get(route('two-factor.recovery-codes'));
                setRecoveryCodes(response.data);
            }
        } catch (error) {
            console.error('Failed to regenerate recovery codes:', error);
        }
    };

    const disableTwoFactorAuthentication = () => {
        setDisabling(true);

        router.delete(route('two-factor.disable'), {
            preserveScroll: true,
            onSuccess: () => {
                setDisabling(false);
                setConfirming(false);
                setRecoveryCodes([]);
                setShowingRecoveryCodes(false);
            }
        });
    };

    return (
        <div className="space-y-6">
            <HeadingSmall
                title="Two-Factor Authentication"
                description="Add an extra layer of security to your account by enabling two-factor authentication"
            />
            <div className="space-y-4">
                <div className="text-sm text-muted-foreground">
                    {twoFactorEnabled ? (
                        <span className="text-green-600 font-medium">Two-factor authentication is enabled.</span>
                    ) : confirming ? (
                        <span className="text-amber-600 font-medium">Finish enabling two-factor authentication.</span>
                    ) : (
                        <span>Two-factor authentication is not enabled.</span>
                    )}
                </div>

                {error && (
                    <Alert variant="destructive">
                        <AlertDescription>{error}</AlertDescription>
                    </Alert>
                )}

                {(twoFactorEnabled || confirming) && (
                    <div className="space-y-4">
                        {qrCode && (
                            <div className="space-y-4">
                                <div>
                                    <p className="text-sm font-medium mb-2">
                                        {confirming
                                            ? 'To finish enabling two-factor authentication, scan the QR code with your authenticator app or enter the setup key manually.'
                                            : 'Scan this QR code with your authenticator app.'}
                                    </p>
                                </div>

                                <div className="flex flex-col items-start space-y-4">
                                    <div
                                        className="bg-white dark:bg-gray-100 p-2 rounded-lg border w-fit [&>svg]:w-[150px] [&>svg]:h-[150px]"
                                        dangerouslySetInnerHTML={{ __html: qrCode }}
                                    />

                                    {setupKey && (
                                        <div className="space-y-2">
                                            <Label className="text-sm">Setup Key</Label>
                                            <code className="text-xs bg-muted px-2 py-1 rounded">
                                                {setupKey}
                                            </code>
                                        </div>
                                    )}
                                </div>

                                {confirming && (
                                    <form onSubmit={confirmTwoFactorAuthentication} className="space-y-4">
                                        <div className="space-y-2">
                                            <Label htmlFor="code">Confirmation Code</Label>
                                            <Input
                                                id="code"
                                                type="text"
                                                placeholder="Enter 6-digit code"
                                                value={confirmationCode}
                                                onChange={(e) => setConfirmationCode(e.target.value)}
                                                maxLength={6}
                                                pattern="[0-9]{6}"
                                                required
                                            />
                                        </div>
                                        <Button type="submit" size="sm">
                                            Confirm
                                        </Button>
                                    </form>
                                )}
                            </div>
                        )}

                        {twoFactorEnabled && (
                            <div className="space-y-4">
                                <Separator />

                                <div className="space-y-2">
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        onClick={toggleRecoveryCodes}
                                        className="gap-2"
                                    >
                                        {showingRecoveryCodes ? (
                                            <>
                                                <EyeOff className="h-4 w-4" />
                                                Hide Recovery Codes
                                            </>
                                        ) : (
                                            <>
                                                <Eye className="h-4 w-4" />
                                                Show Recovery Codes
                                            </>
                                        )}
                                    </Button>

                                    {showingRecoveryCodes && recoveryCodes.length > 0 && (
                                        <div className="space-y-3">
                                            <Alert>
                                                <AlertDescription>
                                                    Store these recovery codes in a secure location. They can be used to access your account if you lose your two-factor authentication device.
                                                </AlertDescription>
                                            </Alert>

                                            <div className="grid grid-cols-2 gap-2 p-4 bg-muted/50 rounded-lg">
                                                {recoveryCodes.map((code, index) => (
                                                    <code key={index} className="text-sm">
                                                        {code}
                                                    </code>
                                                ))}
                                            </div>

                                            <Button
                                                variant="outline"
                                                size="sm"
                                                onClick={regenerateRecoveryCodes}
                                                className="gap-2"
                                            >
                                                <RefreshCw className="h-4 w-4" />
                                                Regenerate Recovery Codes
                                            </Button>
                                        </div>
                                    )}
                                </div>
                            </div>
                        )}
                    </div>
                )}

                <div className="flex gap-2">
                    {!twoFactorEnabled && !confirming && (
                        <Button
                            onClick={enableTwoFactorAuthentication}
                            disabled={enabling}
                            size="sm"
                        >
                            {enabling ? 'Enabling...' : 'Enable Two-Factor Authentication'}
                        </Button>
                    )}

                    {twoFactorEnabled && (
                        <Button
                            variant="destructive"
                            onClick={disableTwoFactorAuthentication}
                            disabled={disabling}
                            size="sm"
                        >
                            {disabling ? 'Disabling...' : 'Disable Two-Factor Authentication'}
                        </Button>
                    )}
                </div>
            </div>
        </div>
    );
}
