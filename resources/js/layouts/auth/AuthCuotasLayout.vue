<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import LocaleSwitcher from '@/components/cuotas/LocaleSwitcher.vue';
import { useTrans } from '@/composables/useTrans';
import { home } from '@/routes';

defineProps<{
    title?: string;
    description?: string;
}>();

const { t } = useTrans();
</script>

<template>
    <div
        class="bg-cuotas-bg flex min-h-svh items-center justify-center p-3.5 sm:p-7"
    >
        <div
            class="bg-cuotas-surface grid w-full max-w-[900px] overflow-hidden rounded-[var(--radius-cuotas-xl)] shadow-[var(--shadow-cuotas)] md:grid-cols-2"
        >
            <div
                class="bg-cuotas-bg relative hidden flex-col justify-between overflow-hidden p-11 md:flex"
            >
                <Link
                    :href="home()"
                    class="z-10 flex items-center gap-2 font-[family-name:var(--font-head)] text-[19px] font-bold"
                >
                    <span
                        class="bg-cuotas-ink inline-block h-[9px] w-[9px] rounded-full"
                    />
                    Cuotas
                </Link>
                <p
                    class="z-10 font-[family-name:var(--font-head)] text-[26px] leading-snug font-semibold"
                >
                    {{ t('auth.tagline') }}
                </p>
                <div
                    class="border-cuotas-ink relative z-10 w-[210px] rounded-[14px] border-2 bg-white p-3 shadow-[6px_6px_0_rgba(22,22,22,0.12)]"
                >
                    <div class="mb-2 flex gap-1">
                        <i
                            class="border-cuotas-ink h-1.5 w-1.5 rounded-full border"
                        />
                        <i
                            class="border-cuotas-ink h-1.5 w-1.5 rounded-full border"
                        />
                        <i
                            class="border-cuotas-ink h-1.5 w-1.5 rounded-full border"
                        />
                    </div>
                    <div
                        class="border-cuotas-border flex justify-between border-t border-dashed py-1.5 text-[10px] first:border-t-0"
                    >
                        <span>Fotostanisic</span><span>$ 3.936</span>
                    </div>
                    <div
                        class="border-cuotas-border flex justify-between border-t border-dashed py-1.5 text-[10px]"
                    >
                        <span>Wikimusculos</span><span>$ 1.660</span>
                    </div>
                </div>
                <p class="text-cuotas-muted z-10 text-xs">
                    {{ t('auth.tagline_small') }}
                </p>
            </div>

            <div class="flex flex-col justify-center p-8 sm:p-11">
                <div class="mb-4 flex justify-end">
                    <LocaleSwitcher />
                </div>
                <p
                    v-if="title"
                    class="text-cuotas-muted mb-2 text-[13px] font-medium"
                >
                    {{ description ? t('auth.account') : '' }}
                </p>
                <h1 v-if="title" class="mb-2 text-[27px] font-semibold">
                    {{ title }}
                </h1>
                <p
                    v-if="description"
                    class="text-cuotas-muted mb-6 text-sm leading-relaxed"
                >
                    {{ description }}
                </p>
                <slot />
            </div>
        </div>
    </div>
</template>
