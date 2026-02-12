import React, { forwardRef, useEffect, useImperativeHandle, useRef, useState } from 'react';
import Reaptcha from 'reaptcha';

export type CaptchaProvider = 'google' | 'turnstile' | 'hcaptcha';

export interface CaptchaWidgetHandle {
    execute: () => Promise<void>;
    reset: () => void;
}

interface Props {
    provider: CaptchaProvider;
    siteKey: string;
    onVerify: (token: string) => void;
    onExpire: () => void;
}

type RenderOptions = {
    sitekey: string;
    callback: (token: string) => void;
    'expired-callback': () => void;
};

type TurnstileApi = {
    render: (container: HTMLElement, options: RenderOptions) => string;
    reset: (widgetId: string) => void;
    remove?: (widgetId: string) => void;
};

type HcaptchaApi = {
    render: (container: HTMLElement, options: RenderOptions) => string | number;
    reset: (widgetId: string | number) => void;
};

declare global {
    interface Window {
        turnstile?: TurnstileApi;
        hcaptcha?: HcaptchaApi;
    }
}

const SCRIPT_URLS: Record<CaptchaProvider, string> = {
    google: 'https://www.google.com/recaptcha/api.js',
    turnstile: 'https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit',
    hcaptcha: 'https://js.hcaptcha.com/1/api.js?render=explicit',
};

const CaptchaWidget = forwardRef<CaptchaWidgetHandle, Props>(({ provider, siteKey, onVerify, onExpire }, ref) => {
    const googleRef = useRef<Reaptcha>(null);
    const containerRef = useRef<HTMLDivElement>(null);
    const onVerifyRef = useRef(onVerify);
    const onExpireRef = useRef(onExpire);
    const [widgetId, setWidgetId] = useState<string | number | null>(null);

    useEffect(() => {
        onVerifyRef.current = onVerify;
        onExpireRef.current = onExpire;
    }, [onVerify, onExpire]);

    const loadScript = (src: string, id: string): Promise<void> => {
        if (document.getElementById(id)) {
            return Promise.resolve();
        }

        return new Promise((resolve, reject) => {
            const script = document.createElement('script');
            script.id = id;
            script.src = src;
            script.async = true;
            script.defer = true;
            script.onload = () => resolve();
            script.onerror = () => reject(new Error(`Unable to load captcha provider script: ${src}`));
            document.head.appendChild(script);
        });
    };

    const resetCustomWidget = () => {
        if (provider === 'turnstile' && widgetId && window.turnstile) {
            window.turnstile.reset(String(widgetId));
        }

        if (provider === 'hcaptcha' && widgetId !== null && window.hcaptcha) {
            window.hcaptcha.reset(widgetId);
        }
    };

    useImperativeHandle(
        ref,
        () => ({
            execute: async () => {
                if (provider === 'google') {
                    await googleRef.current?.execute();
                }
            },
            reset: () => {
                if (provider === 'google') {
                    googleRef.current?.reset();
                    return;
                }

                resetCustomWidget();
            },
        }),
        [provider, widgetId]
    );

    useEffect(() => {
        if (provider === 'google') {
            return;
        }

        const id = `captcha-script-${provider}`;
        const init = async () => {
            await loadScript(SCRIPT_URLS[provider], id);

            if (!containerRef.current) {
                return;
            }

            containerRef.current.innerHTML = '';

            if (provider === 'turnstile' && window.turnstile) {
                const createdWidgetId = window.turnstile.render(containerRef.current, {
                    sitekey: siteKey,
                    callback: (token: string) => onVerifyRef.current(token),
                    'expired-callback': () => onExpireRef.current(),
                });
                setWidgetId(createdWidgetId);
            }

            if (provider === 'hcaptcha' && window.hcaptcha) {
                const createdWidgetId = window.hcaptcha.render(containerRef.current, {
                    sitekey: siteKey,
                    callback: (token: string) => onVerifyRef.current(token),
                    'expired-callback': () => onExpireRef.current(),
                });
                setWidgetId(createdWidgetId);
            }
        };

        init().catch(() => {
            onExpireRef.current();
        });
    }, [provider, siteKey]);

    if (provider === 'google') {
        return (
            <Reaptcha
                ref={googleRef}
                size={'invisible'}
                sitekey={siteKey || '_invalid_key'}
                onVerify={onVerify}
                onExpire={onExpire}
            />
        );
    }

    return <div ref={containerRef} />;
});

CaptchaWidget.displayName = 'CaptchaWidget';

export default CaptchaWidget;
