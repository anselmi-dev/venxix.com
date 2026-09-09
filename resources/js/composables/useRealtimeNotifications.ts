import { usePage } from '@inertiajs/vue3';
import { onMounted, onUnmounted, ref, watch } from 'vue';
import { getEcho } from '@/echo';

export type RealtimeNotification = {
    id: string;
    icon: string;
    title: string;
    description: string;
    url: string | null;
    unread: boolean;
    time: string;
};

export function useRealtimeNotifications(
    initialUnread: number,
    initialLatest: RealtimeNotification[],
) {
    const unreadCount = ref(initialUnread);
    const latest = ref<RealtimeNotification[]>(initialLatest);

    watch(
        () => usePage().props.notifications,
        (notifications) => {
            if (!notifications) {
                return;
            }

            unreadCount.value = notifications.unread_count;
            latest.value = notifications.latest;
        },
    );

    let channelName: string | null = null;

    onMounted(() => {
        const page = usePage();
        const userId = page.props.auth.user?.id;
        const realtime = page.props.realtime;

        if (!userId || !realtime?.enabled) {
            return;
        }

        const echo = getEcho();

        if (!echo) {
            return;
        }

        channelName = `App.Models.User.${userId}`;

        echo.private(channelName).notification(
            (notification: Record<string, unknown>) => {
                unreadCount.value += 1;

                latest.value = [
                    {
                        id: String(notification.id ?? crypto.randomUUID()),
                        icon: String(notification.icon ?? '🔔'),
                        title: String(notification.title ?? 'Notificación'),
                        description: String(notification.description ?? ''),
                        url: notification.url
                            ? String(notification.url)
                            : null,
                        unread: true,
                        time: 'Ahora',
                    },
                    ...latest.value.slice(0, 4),
                ];
            },
        );
    });

    onUnmounted(() => {
        if (!channelName) {
            return;
        }

        const echo = getEcho();

        if (echo) {
            echo.leave(channelName);
        }
    });

    return {
        unreadCount,
        latest,
    };
}
