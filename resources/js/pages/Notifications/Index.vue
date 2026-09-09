<script setup lang="ts">
import { Form, Head, Link, router } from '@inertiajs/vue3';
import { useTrans } from '@/composables/useTrans';
import {
    index as notificationsIndex,
    read,
    readAll,
} from '@/routes/notifications';
import { panel } from '@/routes';

defineOptions({
    layout: {
        active: 'panel',
    },
});

defineProps<{
    filter: 'all' | 'unread';
    unreadCount: number;
    totalCount: number;
    groups: {
        label: string;
        items: {
            id: string;
            icon: string;
            title: string;
            description: string;
            url: string | null;
            unread: boolean;
            time: string;
        }[];
    }[];
}>();

const { t } = useTrans();

function setFilter(filter: 'all' | 'unread'): void {
    router.get(
        notificationsIndex.url({ query: { filter } }),
        {},
        { preserveState: true },
    );
}
</script>

<template>
    <Head :title="t('notifications.title')" />

    <Link
        :href="panel()"
        class="text-cuotas-muted hover:text-cuotas-ink mb-5 inline-flex text-[13px]"
        >{{ t('summary.back_panel') }}</Link
    >

    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div>
            <p class="text-cuotas-muted mb-2 text-[13px] font-medium">
                {{ t('notifications.account') }}
            </p>
            <h1 class="mb-1.5 text-[28px] font-semibold">
                {{ t('notifications.title') }}
            </h1>
            <p class="text-cuotas-muted max-w-[52ch] text-sm leading-relaxed">
                {{ t('notifications.lead') }}
            </p>
        </div>
        <Form
            v-if="unreadCount > 0"
            v-bind="readAll.form()"
            #default="{ processing }"
        >
            <button
                type="submit"
                :disabled="processing"
                class="border-cuotas-border bg-cuotas-surface-2 rounded-full border px-4 py-2 text-[13px] font-semibold disabled:opacity-60"
            >
                {{ t('notifications.mark_all') }}
            </button>
        </Form>
    </div>

    <div
        class="border-cuotas-border bg-cuotas-surface-2 mb-2 flex w-fit gap-1.5 rounded-full border p-1"
    >
        <button
            type="button"
            class="flex items-center gap-1.5 rounded-full px-4 py-2 text-[13px] font-semibold"
            :class="
                filter === 'all'
                    ? 'bg-cuotas-ink text-white'
                    : 'text-cuotas-muted'
            "
            @click="setFilter('all')"
        >
            {{ t('notifications.all') }}
            <span
                class="rounded-full px-1.5 text-[10px] font-bold"
                :class="
                    filter === 'all'
                        ? 'bg-white/25 text-white'
                        : 'bg-cuotas-red text-white'
                "
                >{{ totalCount }}</span
            >
        </button>
        <button
            type="button"
            class="flex items-center gap-1.5 rounded-full px-4 py-2 text-[13px] font-semibold"
            :class="
                filter === 'unread'
                    ? 'bg-cuotas-ink text-white'
                    : 'text-cuotas-muted'
            "
            @click="setFilter('unread')"
        >
            {{ t('notifications.unread') }}
            <span
                class="bg-cuotas-red rounded-full px-1.5 text-[10px] font-bold text-white"
                >{{ unreadCount }}</span
            >
        </button>
    </div>

    <div
        class="border-cuotas-border bg-cuotas-surface-2 rounded-[var(--radius-cuotas-lg)] border px-5 py-7 sm:px-8"
    >
        <p
            v-if="groups.length === 0"
            class="text-cuotas-muted py-10 text-center text-sm"
        >
            {{
                filter === 'unread'
                    ? t('notifications.empty_unread')
                    : t('notifications.empty_all')
            }}
        </p>

        <div v-for="group in groups" :key="group.label" class="mb-2 last:mb-0">
            <p
                class="text-cuotas-muted mt-6 mb-3.5 text-xs font-bold tracking-wide uppercase first:mt-0"
            >
                {{ group.label }}
            </p>
            <div class="flex flex-col">
                <div
                    v-for="(item, index) in group.items"
                    :key="item.id"
                    class="flex gap-3.5"
                >
                    <span
                        class="text-cuotas-muted w-[52px] shrink-0 pt-0.5 text-right font-[family-name:var(--font-head)] text-xs font-semibold"
                        >{{ item.time }}</span
                    >
                    <div class="flex w-[18px] shrink-0 flex-col items-center">
                        <span
                            class="mt-1 h-2.5 w-2.5 shrink-0 rounded-full border-2 bg-white"
                            :class="
                                item.unread
                                    ? 'border-cuotas-bg-deep'
                                    : 'border-cuotas-border'
                            "
                        />
                        <span
                            v-if="index < group.items.length - 1"
                            class="bg-cuotas-border my-1 min-h-3.5 w-px flex-1"
                        />
                    </div>
                    <div class="min-w-0 flex-1 pb-5 last:pb-0">
                        <component
                            :is="item.url ? Link : 'div'"
                            :href="item.url ?? undefined"
                            class="flex items-start gap-3 rounded-[var(--radius-cuotas-md)] border bg-white px-4 py-3.5"
                            :class="
                                item.unread
                                    ? 'border-[#dbe98a] shadow-[0_0_0_3px_#f2fad0_inset]'
                                    : 'border-cuotas-border'
                            "
                        >
                            <span
                                class="border-cuotas-border bg-cuotas-surface-2 flex h-9 w-9 shrink-0 items-center justify-center rounded-[10px] border text-base"
                                >{{ item.icon }}</span
                            >
                            <div class="min-w-0 flex-1">
                                <p class="text-[13.5px] font-semibold">
                                    {{ item.title }}
                                </p>
                                <p
                                    class="text-cuotas-muted mt-1 text-[12.5px] leading-relaxed"
                                >
                                    {{ item.description }}
                                </p>
                            </div>
                            <Form
                                v-if="item.unread"
                                v-bind="
                                    read.form({
                                        notification: item.id,
                                    })
                                "
                                class="shrink-0"
                                #default="{ processing }"
                            >
                                <button
                                    type="submit"
                                    :disabled="processing"
                                    class="text-cuotas-muted hover:text-cuotas-ink text-xs disabled:opacity-60"
                                >
                                    {{ t('notifications.mark_read') }}
                                </button>
                            </Form>
                        </component>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
