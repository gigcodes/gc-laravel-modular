import { Head, router } from '@inertiajs/react';
import { Button } from '@shared/components/ui/button';
import { Input } from '@shared/components/ui/input';
import { Label } from '@shared/components/ui/label';
import InputError from '@shared/components/input-error';
import { useState } from 'react';
import AuthLayout from '@shared/layouts/auth-layout';
import { AnimatePresence, motion } from 'framer-motion';
import { LoaderCircle, Shield, KeyRound } from 'lucide-react';

export default function TwoFactorChallenge() {
    const [loading, setLoading] = useState(false);
    const [isRecovery, setIsRecovery] = useState(false);
    const [code, setCode] = useState('');
    const [recoveryCode, setRecoveryCode] = useState('');
    const [errors, setErrors] = useState<Record<string, string>>({});

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        setLoading(true);
        setErrors({});

        const data = isRecovery ? { recovery_code: recoveryCode } : { code: code };

        router.post(route('two-factor.verify'), data, {
            onError: (errors) => {
                setErrors(errors);
                setLoading(false);
            },
        });
    };

    const toggleRecovery = () => {
        setIsRecovery(!isRecovery);
        setCode('');
        setRecoveryCode('');
        setErrors({});
    };

    return (
        <AuthLayout title="Two-factor authentication" description="Enter your authentication code to continue">
            <Head title="Two-factor Confirmation" />
            
            <AnimatePresence mode="wait">
                <motion.form
                    key={isRecovery ? 'recovery' : 'code'}
                    onSubmit={handleSubmit}
                    className="flex flex-col gap-6"
                    initial={{ opacity: 0, x: isRecovery ? 20 : -20 }}
                    animate={{ opacity: 1, x: 0 }}
                    exit={{ opacity: 0, x: isRecovery ? -20 : 20 }}
                    transition={{ duration: 0.3 }}
                >
                    <div className="flex items-center justify-center mb-4">
                        <div className="p-3 bg-primary/10 rounded-full">
                            {isRecovery ? (
                                <KeyRound className="h-8 w-8 text-primary" />
                            ) : (
                                <Shield className="h-8 w-8 text-primary" />
                            )}
                        </div>
                    </div>

                    <div className="text-center mb-4">
                        <p className="text-sm text-muted-foreground">
                            {isRecovery
                                ? 'Enter one of your emergency recovery codes'
                                : 'Enter the 6-digit code from your authenticator app'}
                        </p>
                    </div>

                    {isRecovery ? (
                        <motion.div
                            initial={{ opacity: 0, y: 10 }}
                            animate={{ opacity: 1, y: 0 }}
                            transition={{ delay: 0.1 }}
                            className="grid gap-2"
                        >
                            <Label htmlFor="recovery_code">Recovery Code</Label>
                            <Input
                                type="text"
                                id="recovery_code"
                                name="recovery_code"
                                autoComplete="one-time-code"
                                placeholder="xxxx-xxxx-xxxx"
                                value={recoveryCode}
                                onChange={(e) => setRecoveryCode(e.target.value)}
                                required
                                autoFocus
                            />
                            <InputError message={errors.recovery_code} />
                        </motion.div>
                    ) : (
                        <motion.div
                            initial={{ opacity: 0, y: 10 }}
                            animate={{ opacity: 1, y: 0 }}
                            transition={{ delay: 0.1 }}
                            className="grid gap-2"
                        >
                            <Label htmlFor="code">Authentication Code</Label>
                            <Input
                                type="text"
                                id="code"
                                name="code"
                                autoComplete="one-time-code"
                                inputMode="numeric"
                                placeholder="000000"
                                pattern="[0-9]{6}"
                                maxLength={6}
                                value={code}
                                onChange={(e) => setCode(e.target.value.replace(/[^0-9]/g, ''))}
                                required
                                autoFocus
                                className="text-center text-2xl font-mono tracking-widest"
                            />
                            <InputError message={errors.code} />
                        </motion.div>
                    )}

                    <Button type="submit" className="w-full" disabled={loading}>
                        {loading ? (
                            <>
                                <LoaderCircle className="h-4 w-4 animate-spin" />
                                Verifying...
                            </>
                        ) : (
                            'Verify'
                        )}
                    </Button>

                    <div className="relative">
                        <div className="absolute inset-0 flex items-center">
                            <span className="w-full border-t" />
                        </div>
                        <div className="relative flex justify-center text-xs uppercase">
                            <span className="bg-background px-2 text-muted-foreground">
                                Or
                            </span>
                        </div>
                    </div>

                    <Button
                        type="button"
                        variant="ghost"
                        className="w-full"
                        onClick={toggleRecovery}
                    >
                        {isRecovery ? 'Use authentication code instead' : 'Use recovery code instead'}
                    </Button>
                </motion.form>
            </AnimatePresence>
        </AuthLayout>
    );
}
