import type {
    AuthenticationResponseJSON,
    PublicKeyCredentialCreationOptionsJSON,
    PublicKeyCredentialRequestOptionsJSON,
    RegistrationResponseJSON,
} from '@simplewebauthn/browser';
import { browserSupportsWebAuthn, startAuthentication, startRegistration } from '@simplewebauthn/browser';

/**
 * Check if the browser supports WebAuthn
 */
export function isWebAuthnSupported(): boolean {
    return browserSupportsWebAuthn();
}

/**
 * Convert base64url string to ArrayBuffer
 */
export function base64urlToArrayBuffer(base64url: string): ArrayBuffer {
    const base64 = base64url.replace(/-/g, '+').replace(/_/g, '/');
    const padded = base64.padEnd(base64.length + ((4 - (base64.length % 4)) % 4), '=');
    const binary = atob(padded);
    const buffer = new ArrayBuffer(binary.length);
    const bytes = new Uint8Array(buffer);
    for (let i = 0; i < binary.length; i++) {
        bytes[i] = binary.charCodeAt(i);
    }
    return buffer;
}

/**
 * Convert ArrayBuffer to base64url string
 */
export function arrayBufferToBase64url(buffer: ArrayBuffer): string {
    const bytes = new Uint8Array(buffer);
    let binary = '';
    for (let i = 0; i < bytes.byteLength; i++) {
        binary += String.fromCharCode(bytes[i]);
    }
    const base64 = btoa(binary);
    return base64.replace(/\+/g, '-').replace(/\//g, '_').replace(/=/g, '');
}

/**
 * Start passkey registration process
 */
export async function registerPasskey(options: PublicKeyCredentialCreationOptionsJSON): Promise<RegistrationResponseJSON> {
    if (!isWebAuthnSupported()) {
        throw new Error('WebAuthn is not supported in this browser');
    }

    try {
        return await startRegistration({ optionsJSON: options });
    } catch (error) {
        if (error instanceof Error) {
            throw new Error(`Registration failed: ${error.message}`);
        }
        throw new Error('Registration failed: Unknown error');
    }
}

/**
 * Start passkey authentication process
 */
export async function authenticateWithPasskey(options: PublicKeyCredentialRequestOptionsJSON): Promise<AuthenticationResponseJSON> {
    if (!isWebAuthnSupported()) {
        throw new Error('WebAuthn is not supported in this browser');
    }

    try {
        return await startAuthentication({ optionsJSON: options });
    } catch (error) {
        if (error instanceof Error) {
            throw new Error(`Authentication failed: ${error.message}`);
        }
        throw new Error('Authentication failed: Unknown error');
    }
}

/**
 * Get user-friendly error message for WebAuthn errors
 */
export function getWebAuthnErrorMessage(error: Error): string {
    if (process.env.NODE_ENV !== 'production') {
        console.group('🔐 WebAuthn Error Analysis');
        console.error('Original error:', error);
        console.error('Error name:', error.name);
        console.error('Error message:', error.message);
        console.error('Error stack:', error.stack);
    }

    const message = error.message.toLowerCase();

    if (process.env.NODE_ENV !== 'production') {
        console.log('Normalized message:', message);
    }

    let userMessage = '';

    if (message.includes('not allowed')) {
        userMessage = 'The operation was not allowed. Please try again.';
    } else if (message.includes('timeout')) {
        userMessage = 'The operation timed out. Please try again.';
    } else if (message.includes('invalid state')) {
        userMessage = 'This passkey is already registered or invalid.';
    } else if (message.includes('not supported')) {
        userMessage = 'Passkeys are not supported in this browser.';
    } else if (message.includes('security')) {
        userMessage = 'Security error. Please ensure you are on a secure connection (HTTPS).';
    } else {
        userMessage = 'An error occurred. Please try again.';
    }

    if (process.env.NODE_ENV !== 'production') {
        console.log('User-friendly message:', userMessage);
        console.groupEnd();
    }

    return userMessage;
}

/**
 * Check if the current context supports passkeys
 */
export function canUsePasskeys(): boolean {
    return isWebAuthnSupported() && window.isSecureContext && (window.location.protocol === 'https:' || window.location.hostname === 'localhost');
}

/**
 * Format passkey creation options for the browser
 */
interface RegistrationOptionsInput {
    challenge: string;
    user: {
        id: string;
        [key: string]: unknown;
    };
    excludeCredentials?: Array<{
        id: string;
        [key: string]: unknown;
    }>;
    [key: string]: unknown;
}

export function formatRegistrationOptions(options: RegistrationOptionsInput): PublicKeyCredentialCreationOptionsJSON {
    return {
        ...options,
        challenge: options.challenge,
        user: {
            ...options.user,
            id: options.user.id,
        },
        excludeCredentials:
            options.excludeCredentials?.map((cred) => ({
                ...cred,
                id: cred.id,
            })) || [],
    };
}

/**
 * Format passkey authentication options for the browser
 */
interface AuthenticationOptionsInput {
    challenge: string;
    allowCredentials?: Array<{
        id: string;
        [key: string]: unknown;
    }>;
    [key: string]: unknown;
}

export function formatAuthenticationOptions(options: AuthenticationOptionsInput): PublicKeyCredentialRequestOptionsJSON {
    return {
        ...options,
        challenge: options.challenge,
        allowCredentials:
            options.allowCredentials?.map((cred) => ({
                ...cred,
                id: cred.id,
            })) || [],
    };
}
