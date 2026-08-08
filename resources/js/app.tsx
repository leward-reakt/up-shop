import { createInertiaApp } from '@inertiajs/react';
import { Toaster } from '@/components/ui/sonner';
import { TooltipProvider } from '@/components/ui/tooltip';
import { initializeTheme } from '@/hooks/use-appearance';
import AppLayout from '@/layouts/app-layout';
import AuthLayout from '@/layouts/auth-layout';
import SettingsLayout from '@/layouts/settings/layout';

const appName = import.meta.env.VITE_APP_NAME || 'Up Shop';

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),

    layout: (name) => {
        switch (true) {
            // Public storefront pages own their StorefrontLayout directly.
            case name === 'welcome':
            case name === 'home':
            case name.startsWith('shop/'):
            case name.startsWith('cart/'):
            case name.startsWith('checkout/'):
            case name.startsWith('pages/'):
                return null;

            // These customer-account pages choose between the existing
            // application layout and the selected storefront theme at runtime.
            case name === 'dashboard':
            case name.startsWith('account/'):
            case name === 'settings/profile':
            case name === 'settings/security':
                return null;

            case name.startsWith('auth/'):
                return AuthLayout;

            // Appearance remains an application preference rather than part of
            // the customer-facing storefront theme.
            case name.startsWith('settings/'):
                return [AppLayout, SettingsLayout];

            default:
                return AppLayout;
        }
    },

    strictMode: true,

    withApp(app) {
        return (
            <TooltipProvider delayDuration={0}>
                {app}
                <Toaster />
            </TooltipProvider>
        );
    },

    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on load...
initializeTheme();
