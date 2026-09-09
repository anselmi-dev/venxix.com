<script setup lang="ts">
import { Form, Link } from '@inertiajs/vue3';
import { onMounted, onUnmounted, ref } from 'vue';
import { Bell } from '@animated-color-icons/lucide-vue';
import {
    type RealtimeNotification,
    useRealtimeNotifications,
} from '@/composables/useRealtimeNotifications';
import { useTrans } from '@/composables/useTrans';
import { index as notificationsIndex, readAll } from '@/routes/notifications';

const props = defineProps<{
    unreadCount: number;
    latest: RealtimeNotification[];
}>();

const { unreadCount, latest } = useRealtimeNotifications(
    props.unreadCount,
    props.latest,
);

const { t } = useTrans();
const open = ref(false);

function toggle(): void {
    open.value = !open.value;
}

function closeOnOutside(event: MouseEvent): void {
    const target = event.target as HTMLElement | null;

    if (target?.closest('[data-notif-bell]')) {
        return;
    }

    open.value = false;
}

onMounted(() => {
    document.addEventListener('click', closeOnOutside);
});

onUnmounted(() => {
    document.removeEventListener('click', closeOnOutside);
});
</script>

<template>
    <div class="relative" data-notif-bell>
        <button
            type="button"
            class="al-icon-wrapper bg-cuotas-surface-2 relative flex h-9 w-9 items-center justify-center rounded-xl text-sm"
            :aria-label="t('notifications.aria')"
            @click.stop="toggle"
        >
            <Bell class="h-4 w-4" />
            <span
                v-if="unreadCount > 0"
                class="bg-cuotas-red absolute top-1.5 right-1.5 h-2 w-2 rounded-full"
            />
        </button>

        <div
            v-if="open"
            class="animate-in fade-in-0 zoom-in-95 slide-in-from-top-1 border-cuotas-border absolute top-11 right-0 z-20 w-[320px] origin-top-right overflow-hidden rounded-[var(--radius-cuotas-md)] border bg-white shadow-[var(--shadow-cuotas-sm)] duration-150"
        >
            <div
                class="border-cuotas-border flex items-center justify-between border-b px-4 py-3"
            >
                <h4 class="text-sm font-semibold">
                    {{ t('notifications.title') }}
                </h4>
                <Form
                    v-if="unreadCount > 0"
                    v-bind="readAll.form()"
                    class="inline"
                >
                    <button
                        type="submit"
                        class="text-cuotas-ink text-[11px] font-semibold"
                    >
                        {{ t('notifications.mark_all') }}
                    </button>
                </Form>
            </div>

            <div
                v-if="latest.length === 0"
                class="text-cuotas-muted px-4 py-8 text-center text-[13px]"
            >
                {{ t('notifications.empty') }}
            </div>

            <div v-else class="max-h-80 overflow-y-auto">
                <component
                    :is="item.url ? Link : 'div'"
                    v-for="item in latest"
                    :key="item.id"
                    :href="item.url ?? undefined"
                    class="border-cuotas-border flex gap-3 border-b px-4 py-3 last:border-b-0"
                    :class="{ 'bg-[#f2fad0]': item.unread }"
                >
                    <span class="text-base">{{ item.icon }}</span>
                    <div class="min-w-0 flex-1">
                        <p class="text-[13px] font-semibold">
                            {{ item.title }}
                        </p>
                        <p class="text-cuotas-muted truncate text-[12px]">
                            {{ item.description }}
                        </p>
                        <p class="text-cuotas-muted mt-1 text-[11px]">
                            {{ item.time }}
                        </p>
                    </div>
                    <span
                        v-if="item.unread"
                        class="bg-cuotas-bg-deep mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full"
                    />
                </component>
            </div>

            <div class="border-cuotas-border border-t px-4 py-3">
                <Link
                    :href="notificationsIndex()"
                    class="text-cuotas-ink text-[13px] font-semibold"
                    @click="open = false"
                >
                    {{ t('notifications.see_all') }}
                </Link>
            </div>
        </div>
    </div>
</template>
