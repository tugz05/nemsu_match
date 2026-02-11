export * from './auth';
export * from './navigation';
export * from './post';
export * from './ui';

import type { Auth } from './auth';

export type BrandingProps = {
    app_logo_url: string | null;
    header_icon_url: string | null;
};

export type SubscriptionProps = {
    freemium_enabled: boolean;
    is_plus: boolean;
};

export type AppPageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    name: string;
    auth: Auth;
    sidebarOpen: boolean;
    branding: BrandingProps;
    subscription: SubscriptionProps;
    [key: string]: unknown;
};
