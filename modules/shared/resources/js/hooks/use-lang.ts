import { usePage } from '@inertiajs/react';
import { useCallback, useEffect, useMemo, useState } from 'react';
import { SharedData } from '@shared/types';

export type Language = 'en' | 'ja';

const setCookie = (name: string, value: string, days = 365) => {
    if (typeof document === 'undefined') {
        return;
    }

    const maxAge = days * 24 * 60 * 60;
    document.cookie = `${name}=${value};path=/;max-age=${maxAge};SameSite=Lax`;
};

const applyLanguage = (lang: Language) => {
    if (typeof document === 'undefined') {
        return;
    }

    // Set the HTML lang attribute
    document.documentElement.lang = lang;

    // Store in cookie for SSR
    setCookie('locale', lang);
};

export function useLang() {
    const { translations } = usePage<SharedData>().props;
    const [language, setLanguage] = useState<Language>('en');

    const updateLanguage = useCallback((lang: Language) => {
        setLanguage(lang);

        // Store in localStorage for client-side persistence
        localStorage.setItem('locale', lang);

        // Apply the language
        applyLanguage(lang);
    }, []);

    useEffect(() => {
        const savedLanguage = localStorage.getItem('locale') as Language | null;
        updateLanguage(savedLanguage || 'en');
    }, [updateLanguage]);

    const translate = useMemo(() => {
        return (key: string, replacements: Record<string, string> = {}): string => {
            const normalizedKey = key.replace(/::/g, '.');
            let translation = translations[normalizedKey] ?? key;

            Object.keys(replacements).forEach((r) => {
                translation = translation.replace(`:${r}`, replacements[r]);
            });

            return translation;
        };
    }, [translations]);

    // Create aliases for different translation function syntaxes
    const t = translate;
    const __ = translate;
    const trans = translate;

    return {
        language,
        updateLanguage,
        t,
        __,
        trans,
    } as const;
}
