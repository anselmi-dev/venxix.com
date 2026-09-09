<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useTrans } from '@/composables/useTrans';
import { home, login, panel, pricing } from '@/routes';

const props = defineProps<{
    status: number;
}>();

const { t } = useTrans();

const copy = computed(() => {
    switch (props.status) {
        case 403:
            return {
                title: t('errors.403_title'),
                subtitle: t('errors.403_subtitle'),
            };
        case 500:
        case 503:
            return {
                title: t('errors.500_title'),
                subtitle: t('errors.500_subtitle'),
            };
        default:
            return {
                title: t('errors.404_title'),
                subtitle: t('errors.404_subtitle'),
            };
    }
});

function retry(): void {
    window.location.reload();
}
</script>

<template>
    <Head :title="`${status}`" />

    <div class="bg-cuotas-bg flex min-h-svh items-center justify-center p-6">
        <div class="relative max-w-[460px] text-center">
            <div
                class="bg-cuotas-bg-deep/40 absolute top-[-30px] left-1/2 h-[220px] w-[220px] -translate-x-1/2 rounded-full blur-2xl"
            />
            <div
                v-if="status === 403"
                class="relative z-10 mb-4 flex justify-center"
            >
                <div
                    class="border-cuotas-border bg-cuotas-surface-2 flex h-14 w-14 items-center justify-center rounded-2xl border-[1.5px] text-[22px]"
                >
                    🔒
                </div>
            </div>
            <p
                v-else
                class="relative z-10 mb-10 flex items-center justify-center gap-2 font-[family-name:var(--font-head)] text-[19px] font-bold"
            >
                <span
                    class="bg-cuotas-bg-deep inline-block h-[9px] w-[9px] rounded-full"
                />
            </p>
            <p
                class="relative z-10 mb-1.5 font-[family-name:var(--font-head)] text-[96px] leading-none font-bold"
            >
                {{ status }}
            </p>
            <p class="relative z-10 mb-2.5 text-[22px] font-semibold">
                {{ copy.title }}
            </p>
            <p
                class="text-cuotas-muted relative z-10 mx-auto mb-8 max-w-[36ch] text-sm leading-relaxed"
            >
                {{ copy.subtitle }}
            </p>
            <div class="relative z-10 flex flex-wrap justify-center gap-2.5">
                <template v-if="status === 403">
                    <Link
                        :href="login()"
                        class="bg-cuotas-ink inline-flex rounded-full px-5 py-2.5 text-[13px] font-semibold text-white"
                    >
                        {{ t('errors.login') }}
                    </Link>
                    <Link
                        :href="pricing()"
                        class="border-cuotas-ink inline-flex rounded-full border-[1.5px] px-5 py-2.5 text-[13px] font-semibold"
                    >
                        {{ t('common.see_plans') }}
                    </Link>
                </template>
                <template v-else-if="status === 500 || status === 503">
                    <button
                        type="button"
                        class="bg-cuotas-ink inline-flex rounded-full px-5 py-2.5 text-[13px] font-semibold text-white"
                        @click="retry"
                    >
                        {{ t('errors.retry') }}
                    </button>
                    <Link
                        :href="home()"
                        class="border-cuotas-ink inline-flex rounded-full border-[1.5px] px-5 py-2.5 text-[13px] font-semibold"
                    >
                        {{ t('nav.back_home') }}
                    </Link>
                </template>
                <template v-else>
                    <Link
                        :href="panel()"
                        class="bg-cuotas-ink inline-flex rounded-full px-5 py-2.5 text-[13px] font-semibold text-white"
                    >
                        {{ t('errors.go_panel') }}
                    </Link>
                    <Link
                        :href="home()"
                        class="border-cuotas-ink inline-flex rounded-full border-[1.5px] px-5 py-2.5 text-[13px] font-semibold"
                    >
                        {{ t('nav.back_home') }}
                    </Link>
                </template>
            </div>
            <div
                v-if="status === 500 || status === 503"
                class="border-cuotas-border bg-cuotas-surface-2 text-cuotas-muted relative z-10 mt-5 inline-flex items-center gap-1.5 rounded-full border px-3 py-1.5 text-xs"
            >
                <i
                    class="bg-cuotas-red inline-block h-1.5 w-1.5 rounded-full"
                />
                {{ t('errors.code', { status }) }}
            </div>
        </div>
    </div>
</template>
