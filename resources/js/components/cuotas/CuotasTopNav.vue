<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import LocaleSwitcher from '@/components/cuotas/LocaleSwitcher.vue';
import NotificationBell from '@/components/cuotas/NotificationBell.vue';
import { useTrans } from '@/composables/useTrans';
import { panel, summary } from '@/routes';
import { show as perfil } from '@/routes/perfil';

defineProps<{
    active?: 'panel' | 'summary' | 'profile';
}>();

const page = usePage();
const { t } = useTrans();
</script>

<template>
    <div class="mb-9 flex items-center justify-between">
        <Link
            :href="panel()"
            class="flex items-center gap-2 font-[family-name:var(--font-head)] text-[19px] font-bold"
        >
            <span
                class="bg-cuotas-bg-deep inline-block h-[9px] w-[9px] rounded-full"
            />
            Cuotas
        </Link>
        <div
            class="text-cuotas-muted hidden items-center gap-6 text-sm sm:flex"
        >
            <Link
                :href="panel()"
                :class="
                    active === 'panel'
                        ? 'text-cuotas-ink font-semibold'
                        : 'hover:text-cuotas-ink transition-colors duration-200'
                "
            >
                {{ t('nav.panel') }}
            </Link>
            <Link
                :href="summary()"
                :class="
                    active === 'summary'
                        ? 'text-cuotas-ink font-semibold'
                        : 'hover:text-cuotas-ink transition-colors duration-200'
                "
            >
                {{ t('nav.summary') }}
            </Link>
            <Link
                :href="perfil()"
                :class="
                    active === 'profile'
                        ? 'text-cuotas-ink font-semibold'
                        : 'hover:text-cuotas-ink transition-colors duration-200'
                "
            >
                {{ t('nav.profile') }}
            </Link>
        </div>
        <div class="flex items-center gap-3">
            <NotificationBell
                :unread-count="page.props.notifications.unread_count"
                :latest="page.props.notifications.latest"
            />
            <LocaleSwitcher />
            <Link
                :href="perfil()"
                class="bg-cuotas-surface-2 flex h-9 w-9 items-center justify-center rounded-xl text-sm font-semibold"
            >
                {{ page.props.auth.user?.name?.charAt(0) ?? '?' }}
            </Link>
        </div>
    </div>
</template>
