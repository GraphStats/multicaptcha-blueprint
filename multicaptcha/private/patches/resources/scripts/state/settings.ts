import { action, Action } from 'easy-peasy';

type CaptchaProvider = 'google' | 'turnstile' | 'hcaptcha';

export interface SiteSettings {
    name: string;
    locale: string;
    recaptcha: {
        enabled: boolean;
        provider: CaptchaProvider;
        siteKey: string;
    };
    blueprint: {
        disable_attribution: boolean;
    };
}

export interface SettingsStore {
    data?: SiteSettings;
    setSettings: Action<SettingsStore, SiteSettings>;
}

const settings: SettingsStore = {
    data: undefined,

    setSettings: action((state: SettingsStore, payload: SiteSettings) => {
        state.data = payload;
    }),
};

export default settings;
