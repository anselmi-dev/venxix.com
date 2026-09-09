<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import LocaleSwitcher from '@/components/cuotas/LocaleSwitcher.vue';
import { useTrans } from '@/composables/useTrans';
import { home, panel, pricing, register } from '@/routes';
import { privacy, terms } from '@/routes/legal';

withDefaults(
    defineProps<{
        active?: 'home' | 'pricing' | 'terms' | 'privacy';
        variant?: 'marketing' | 'legal';
    }>(),
    {
        variant: 'marketing',
    },
);

const page = usePage();
const { t } = useTrans();
const isAuthenticated = Boolean(page.props.auth.user);
</script>

<template>
    <div class="bg-cuotas-bg min-h-svh p-3.5 sm:p-7">
        <div
            class="bg-cuotas-surface mx-auto max-w-[1120px] rounded-[var(--radius-cuotas-xl)] p-6 shadow-[var(--shadow-cuotas)] sm:p-10"
        >
            <nav
                class="mb-10 flex flex-wrap items-center justify-between gap-4"
            >
                <Link
                    :href="home()"
                    class="flex items-center gap-2 font-[family-name:var(--font-head)] text-[19px] font-bold"
                >
                    <span
                        class="bg-cuotas-bg-deep inline-block h-[9px] w-[9px] rounded-full"
                    />
                    Cuotas
                </Link>

                <div
                    v-if="variant === 'legal'"
                    class="text-cuotas-muted hidden items-center gap-6 text-sm sm:flex"
                >
                    <Link
                        :href="terms()"
                        :class="
                            active === 'terms'
                                ? 'text-cuotas-ink font-semibold'
                                : 'hover:text-cuotas-ink transition-colors duration-200'
                        "
                    >
                        {{ t('nav.terms') }}
                    </Link>
                    <Link
                        :href="privacy()"
                        :class="
                            active === 'privacy'
                                ? 'text-cuotas-ink font-semibold'
                                : 'hover:text-cuotas-ink transition-colors duration-200'
                        "
                    >
                        {{ t('nav.privacy') }}
                    </Link>
                </div>
                <div
                    v-else
                    class="text-cuotas-muted hidden items-center gap-6 text-sm sm:flex"
                >
                    <Link
                        :href="home()"
                        :class="
                            active === 'home'
                                ? 'text-cuotas-ink font-semibold'
                                : 'hover:text-cuotas-ink transition-colors duration-200'
                        "
                    >
                        {{ t('nav.home') }}
                    </Link>
                    <Link
                        :href="pricing()"
                        :class="
                            active === 'pricing'
                                ? 'text-cuotas-ink font-semibold'
                                : 'hover:text-cuotas-ink transition-colors duration-200'
                        "
                    >
                        {{ t('nav.pricing') }}
                    </Link>
                </div>

                <div class="flex items-center gap-3">
                    <LocaleSwitcher />
                    <Link
                        v-if="variant === 'legal'"
                        :href="home()"
                        class="border-cuotas-ink inline-flex rounded-full border-[1.5px] px-4 py-2 text-[13px] font-semibold transition-transform duration-200 hover:scale-[1.03] active:scale-[0.97]"
                    >
                        {{ t('nav.back_home') }}
                    </Link>
                    <Link
                        v-else-if="isAuthenticated"
                        :href="panel()"
                        class="bg-cuotas-ink inline-flex rounded-full px-5 py-2.5 text-[13px] font-semibold text-white transition-transform duration-200 hover:scale-[1.03] active:scale-[0.97]"
                    >
                        {{ t('nav.go_panel') }}
                    </Link>
                    <Link
                        v-else
                        :href="register()"
                        class="bg-cuotas-ink inline-flex rounded-full px-5 py-2.5 text-[13px] font-semibold text-white transition-transform duration-200 hover:scale-[1.03] active:scale-[0.97]"
                    >
                        {{ t('nav.start_free') }}
                    </Link>
                </div>
            </nav>
            <slot />
        </div>
    </div>
</template>
