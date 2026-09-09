import { usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

function urlBase64ToUint8Array(base64String: string): Uint8Array {
    const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
    const base64 = (base64String + padding)
        .replace(/-/g, '+')
        .replace(/_/g, '/');
    const rawData = window.atob(base64);
    const outputArray = new Uint8Array(rawData.length);

    for (let i = 0; i < rawData.length; i++) {
        outputArray[i] = rawData.charCodeAt(i);
    }

    return outputArray;
}

export function usePushNotifications() {
    const page = usePage();
    const isSupported =
        typeof window !== 'undefined' &&
        'serviceWorker' in navigator &&
        'PushManager' in window;
    const isSubscribed = ref(false);
    const isLoading = ref(false);
    const error = ref<string | null>(null);

    async function ensureServiceWorker(): Promise<ServiceWorkerRegistration> {
        const registration = await navigator.serviceWorker.register('/sw.js');

        await navigator.serviceWorker.ready;

        return registration;
    }

    async function refreshSubscriptionState(): Promise<void> {
        if (!isSupported || !page.props.push?.enabled) {
            isSubscribed.value = false;

            return;
        }

        const registration = await navigator.serviceWorker.getRegistration('/sw.js');
        const subscription = await registration?.pushManager.getSubscription();

        isSubscribed.value = subscription !== null && subscription !== undefined;
    }

    async function subscribe(): Promise<void> {
        error.value = null;

        if (!isSupported || !page.props.push?.enabled || !page.props.push.public_key) {
            error.value = 'Las notificaciones push no están disponibles.';

            return;
        }

        isLoading.value = true;

        try {
            const permission = await Notification.requestPermission();

            if (permission !== 'granted') {
                error.value = 'Necesitamos permiso para enviarte alertas.';

                return;
            }

            const registration = await ensureServiceWorker();
            const subscription = await registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: urlBase64ToUint8Array(
                    page.props.push.public_key,
                ) as BufferSource,
            });

            const json = subscription.toJSON();

            await window.fetch('/push/suscripcion', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN':
                        document
                            .querySelector('meta[name="csrf-token"]')
                            ?.getAttribute('content') ?? '',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    endpoint: json.endpoint,
                    keys: json.keys,
                    content_encoding: 'aesgcm',
                }),
            });

            isSubscribed.value = true;
        } catch (subscribeError) {
            error.value =
                subscribeError instanceof Error
                    ? subscribeError.message
                    : 'No pudimos activar las notificaciones push.';
        } finally {
            isLoading.value = false;
        }
    }

    async function unsubscribe(): Promise<void> {
        error.value = null;
        isLoading.value = true;

        try {
            const registration = await navigator.serviceWorker.getRegistration('/sw.js');
            const subscription = await registration?.pushManager.getSubscription();

            if (!subscription) {
                isSubscribed.value = false;

                return;
            }

            const endpoint = subscription.endpoint;

            await subscription.unsubscribe();

            await window.fetch('/push/suscripcion', {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN':
                        document
                            .querySelector('meta[name="csrf-token"]')
                            ?.getAttribute('content') ?? '',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
                body: JSON.stringify({ endpoint }),
            });

            isSubscribed.value = false;
        } catch (unsubscribeError) {
            error.value =
                unsubscribeError instanceof Error
                    ? unsubscribeError.message
                    : 'No pudimos desactivar las notificaciones push.';
        } finally {
            isLoading.value = false;
        }
    }

    return {
        isSupported,
        isSubscribed,
        isLoading,
        error,
        refreshSubscriptionState,
        subscribe,
        unsubscribe,
    };
}
