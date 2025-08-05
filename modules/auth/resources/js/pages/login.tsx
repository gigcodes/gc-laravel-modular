import { Head, useForm } from '@inertiajs/react';
import { LoaderCircle, Fingerprint, Shield } from 'lucide-react';
import { FormEventHandler, useState } from 'react';
import axios from 'axios';
import { AnimatePresence, motion } from 'framer-motion';

import InputError from '@shared/components/input-error';
import TextLink from '@shared/components/text-link';
import { Button } from '@shared/components/ui/button';
import { Checkbox } from '@shared/components/ui/checkbox';
import { Input } from '@shared/components/ui/input';
import { Label } from '@shared/components/ui/label';
import { Separator } from '@shared/components/ui/separator';
import AuthLayout from '@shared/layouts/auth-layout';
import { usePasskeyAuth } from '@auth/hooks/use-passkey-auth';
import { canUsePasskeys } from '@auth/lib/passkey-utils';

type LoginForm = {
    email: string;
    password: string;
    remember: boolean;
};

interface LoginProps {
    status?: string;
    canResetPassword: boolean;
}

export default function Login({ status, canResetPassword }: LoginProps) {
    const { data, setData, post, processing, errors, reset } = useForm<Required<LoginForm>>({
        email: '',
        password: '',
        remember: false,
    });
    
    const [userHasPasskeys, setUserHasPasskeys] = useState(false);
    const [showPasskeyOption, setShowPasskeyOption] = useState(false);
    const [showPasswordField, setShowPasswordField] = useState(false);
    const [emailSubmitted, setEmailSubmitted] = useState(false);
    const [loading, setLoading] = useState(false);
    const [emailError, setEmailError] = useState<string | null>(null);
    
    const {
        isLoading: passkeyLoading,
        error: passkeyError,
        authenticateUser
    } = usePasskeyAuth();
    
    const passkeySupported = canUsePasskeys();

    const handleEmailSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!data.email) return;
        
        setLoading(true);
        setEmailError(null);
        
        try {
            const response = await axios.post(route('login.check-status'), { email: data.email });
            const { userExists, hasPasskeys } = response.data;
            
            if (!userExists) {
                setEmailError('No account found with this email address.');
                setLoading(false);
                return;
            }
            
            setUserHasPasskeys(hasPasskeys);
            setShowPasskeyOption(hasPasskeys && passkeySupported);
            setEmailSubmitted(true);
            setShowPasswordField(true);
        } catch (error) {
            console.error('Failed to check user status:', error);
            setShowPasswordField(true);
            setEmailSubmitted(true);
        } finally {
            setLoading(false);
        }
    };

    const handlePasskeyLogin = async () => {
        if (!passkeySupported) {
            return;
        }
        await authenticateUser(data.email);
    };

    const handlePasswordSubmit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('login'), {
            onFinish: () => reset('password'),
        });
    };
    
    const handleChangeEmail = () => {
        setEmailSubmitted(false);
        setShowPasswordField(false);
        setData('password', '');
        setUserHasPasskeys(false);
        setShowPasskeyOption(false);
        setEmailError(null);
    };

    return (
        <AuthLayout title="Log in to your account" description="Enter your email and password below to log in">
            <Head title="Log in" />

            <AnimatePresence mode="wait">
                {!showPasswordField ? (
                    <motion.form
                        key="email-form"
                        onSubmit={handleEmailSubmit}
                        className="flex flex-col gap-6"
                        initial={{ opacity: 0, x: -20 }}
                        animate={{ opacity: 1, x: 0 }}
                        exit={{ opacity: 0, x: 20 }}
                        transition={{ duration: 0.3 }}
                    >
                        <div className="grid gap-2">
                            <Label htmlFor="email">Email address</Label>
                            <Input
                                id="email"
                                type="email"
                                required
                                autoFocus
                                autoComplete="email"
                                value={data.email}
                                onChange={(e) => setData('email', e.target.value)}
                                placeholder="email@example.com"
                            />
                            <InputError message={emailError || errors.email} />
                        </div>

                        <Button
                            type="submit"
                            className="w-full"
                            disabled={loading || !data.email}
                        >
                            {loading ? (
                                <>
                                    <LoaderCircle className="h-4 w-4 animate-spin" />
                                    Checking...
                                </>
                            ) : (
                                'Continue'
                            )}
                        </Button>
                        
                        <div className="text-center text-sm text-muted-foreground">
                            Don't have an account?{' '}
                            <TextLink href={route('register')}>
                                Sign up
                            </TextLink>
                        </div>
                    </motion.form>
                ) : (
                    <motion.div
                        key="password-form"
                        initial={{ opacity: 0, x: 20 }}
                        animate={{ opacity: 1, x: 0 }}
                        exit={{ opacity: 0, x: -20 }}
                        transition={{ duration: 0.3 }}
                    >
                        <div className="mb-6">
                            <div className="flex items-center justify-between">
                                <div className="flex-1">
                                    <p className="text-sm text-muted-foreground">Signing in as</p>
                                    <p className="font-medium">{data.email}</p>
                                </div>
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                    onClick={handleChangeEmail}
                                    className="ml-4 flex-shrink-0 self-center"
                                >
                                    Change
                                </Button>
                            </div>
                        </div>

                        {showPasskeyOption && userHasPasskeys && (
                            <motion.div
                                initial={{ opacity: 0, y: 10 }}
                                animate={{ opacity: 1, y: 0 }}
                                transition={{ delay: 0.1 }}
                            >
                                <Button 
                                    type="button" 
                                    variant="outline" 
                                    className="w-full mb-4" 
                                    onClick={handlePasskeyLogin}
                                    disabled={passkeyLoading || processing}
                                >
                                    {passkeyLoading ? (
                                        <>
                                            <LoaderCircle className="h-4 w-4 animate-spin" />
                                            Authenticating...
                                        </>
                                    ) : (
                                        <>
                                            <Fingerprint className="h-4 w-4 mr-2" />
                                            Sign in with Passkey
                                        </>
                                    )}
                                </Button>
                                
                                <div className="relative my-6">
                                    <div className="absolute inset-0 flex items-center">
                                        <Separator className="w-full" />
                                    </div>
                                    <div className="relative flex justify-center text-xs uppercase">
                                        <span className="bg-background px-2 text-muted-foreground">
                                            Or use password
                                        </span>
                                    </div>
                                </div>
                            </motion.div>
                        )}

                        <form onSubmit={handlePasswordSubmit} className="space-y-4">
                            <div className="grid gap-2">
                                <div className="flex items-center">
                                    <Label htmlFor="password">Password</Label>
                                    {canResetPassword && (
                                        <TextLink href={route('password.request')} className="ml-auto text-sm">
                                            Forgot password?
                                        </TextLink>
                                    )}
                                </div>
                                <Input
                                    id="password"
                                    type="password"
                                    required
                                    autoFocus
                                    autoComplete="current-password"
                                    value={data.password}
                                    onChange={(e) => setData('password', e.target.value)}
                                    placeholder="Password"
                                />
                                <InputError message={errors.password} />
                            </div>

                            <div className="flex items-center space-x-3">
                                <Checkbox
                                    id="remember"
                                    name="remember"
                                    checked={data.remember}
                                    onClick={() => setData('remember', !data.remember)}
                                />
                                <Label htmlFor="remember">Remember me</Label>
                            </div>

                            <Button 
                                type="submit" 
                                className="w-full" 
                                disabled={processing}
                            >
                                {processing && <LoaderCircle className="h-4 w-4 animate-spin" />}
                                Log in
                            </Button>
                        </form>
                        
                        <div className="mt-6 text-center text-sm text-muted-foreground">
                            Don't have an account?{' '}
                            <TextLink href={route('register')}>
                                Sign up
                            </TextLink>
                        </div>
                    </motion.div>
                )}
            </AnimatePresence>

            {status && <div className="mb-4 text-center text-sm font-medium text-green-600">{status}</div>}
            
            {passkeyError && (
                <div className="mt-4 text-center text-sm font-medium text-red-600">
                    {passkeyError}
                </div>
            )}
            
            {passkeySupported && emailSubmitted && data.email && !userHasPasskeys && (
                <div className="mt-6 p-3 bg-muted/50 rounded-lg border">
                    <div className="flex items-start gap-3">
                        <Shield className="h-5 w-5 text-muted-foreground mt-0.5" />
                        <div>
                            <p className="text-sm font-medium">Enable Passkey Authentication</p>
                            <p className="text-xs text-muted-foreground mt-1">
                                Set up a passkey after signing in for faster, more secure authentication.
                            </p>
                        </div>
                    </div>
                </div>
            )}
        </AuthLayout>
    );
}
