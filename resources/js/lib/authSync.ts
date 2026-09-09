import { router, usePage } from '@inertiajs/vue3';
import type { Page, SharedPageProps } from '@inertiajs/core';

const STORAGE_KEY = 'auth:user-id';

function extractUserId(props: Page<SharedPageProps>['props']): string {
    return props.auth?.user ? String(props.auth.user.id) : '';
}

function persistUserId(userId: string): void {
    if (localStorage.getItem(STORAGE_KEY) !== userId) {
        localStorage.setItem(STORAGE_KEY, userId);
    }
}

function handleStorageChange(event: StorageEvent): void {
    if (event.key !== STORAGE_KEY) {
        return;
    }

    const currentUserId = extractUserId(usePage().props);

    if ((event.newValue ?? '') !== currentUserId) {
        window.location.reload();
    }
}

export function initializeAuthSync(): void {
    if (typeof window === 'undefined') {
        return;
    }

    router.on('navigate', (event) => {
        persistUserId(extractUserId(event.detail.page.props));
    });

    window.addEventListener('storage', handleStorageChange);
}
