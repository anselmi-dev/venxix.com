import { createInertiaApp } from '@inertiajs/vue3';
import { createApp } from 'vue';
import { initializeTheme } from '@/composables/useAppearance';
import { Toaster } from '@/components/ui/sonner';
import AuthLayout from '@/layouts/AuthLayout.vue';
import CuotasLayout from '@/layouts/CuotasLayout.vue';
import MarketingLayout from '@/layouts/MarketingLayout.vue';
import { initializeAuthSync } from '@/lib/authSync';
import { initializeFlashToast } from '@/lib/flashToast';
import { initializePwa } from '@/pwa';

const appName = import.meta.env.VITE_APP_NAME || 'Cuotas';

void createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    layout: (name) => {
        switch (true) {
            case name === 'Landing':
            case name.startsWith('Errors/'):
                return null;
            case name.startsWith('auth/'):
                return AuthLayout;
            case name.startsWith('Onboarding/'):
                return null;
            case name === 'Pricing':
            case name.startsWith('Legal/'):
                return MarketingLayout;
            default:
                return CuotasLayout;
        }
    },
    progress: {
        color: '#141414',
    },
    defaults: {
        visitOptions: () => ({ viewTransition: true }),
    },
});

initializeTheme();
initializeFlashToast();
initializePwa();
initializeAuthSync();

// Mounted outside Inertia's page tree so flash toasts render on every layout,
// including the ones with no wrapping layout (Landing, Onboarding, Errors).
const toasterHost = document.createElement('div');
document.body.appendChild(toasterHost);
createApp(Toaster).mount(toasterHost);
