<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import LocaleSwitcher from '@/components/cuotas/LocaleSwitcher.vue';
import { useTrans } from '@/composables/useTrans';
import { login, pricing, register } from '@/routes';
import { privacy, terms } from '@/routes/legal';

const { t } = useTrans();
</script>

<template>
    <Head :title="t('landing.meta_title')" />

    <div class="bg-cuotas-bg min-h-svh p-3.5 sm:p-7">
        <div
            class="bg-cuotas-surface mx-auto max-w-[1120px] rounded-[var(--radius-cuotas-xl)] p-6 shadow-[var(--shadow-cuotas)] sm:p-10"
        >
            <nav
                class="mb-14 flex flex-wrap items-center justify-between gap-4"
            >
                <div
                    class="flex items-center gap-2 font-[family-name:var(--font-head)] text-[19px] font-bold"
                >
                    <span
                        class="bg-cuotas-bg-deep inline-block h-[9px] w-[9px] rounded-full"
                    />
                    Cuotas
                </div>
                <div
                    class="text-cuotas-muted hidden items-center gap-6 text-sm md:flex"
                >
                    <span class="text-cuotas-ink font-semibold">{{
                        t('nav.home')
                    }}</span>
                    <Link :href="pricing()" class="hover:text-cuotas-ink">{{
                        t('nav.pricing')
                    }}</Link>
                </div>
                <div class="flex items-center gap-3">
                    <LocaleSwitcher />
                    <Link
                        :href="register()"
                        class="bg-cuotas-ink inline-flex items-center justify-center rounded-full px-5 py-2.5 text-[13px] font-semibold text-white transition-transform duration-200 hover:scale-[1.03] active:scale-[0.97]"
                    >
                        {{ t('nav.start_free') }}
                    </Link>
                </div>
            </nav>

            <section
                class="animate-in fade-in slide-in-from-bottom-2 mb-14 grid items-center gap-10 duration-500 lg:grid-cols-[1.1fr_0.9fr]"
            >
                <div>
                    <p class="text-cuotas-muted mb-2.5 text-[13px] font-medium">
                        {{ t('landing.essence') }}
                    </p>
                    <h1 class="mb-5 text-4xl leading-tight sm:text-[46px]">
                        {{ t('landing.hero_line1') }}<br />{{
                            t('landing.hero_line2')
                        }}
                    </h1>
                    <p
                        class="text-cuotas-muted mb-7 font-[family-name:var(--font-head)] text-xl leading-relaxed sm:text-[22px]"
                    >
                        {{ t('landing.hero_before') }}
                        <b class="text-cuotas-ink font-semibold">{{
                            t('landing.hero_highlight')
                        }}</b>
                        {{ t('landing.hero_after') }}
                        <span class="text-cuotas-muted">{{
                            t('landing.hero_no_sheets')
                        }}</span>
                    </p>
                    <ul class="mb-7 flex flex-col gap-2.5 text-sm">
                        <li>
                            <span class="text-cuotas-muted">01 —</span>
                            {{ t('landing.point_1') }}
                        </li>
                        <li>
                            <span class="text-cuotas-muted">02 —</span>
                            {{ t('landing.point_2') }}
                        </li>
                        <li>
                            <span class="text-cuotas-muted">03 —</span>
                            {{ t('landing.point_3') }}
                        </li>
                    </ul>
                    <div class="flex flex-wrap gap-3">
                        <Link
                            :href="register()"
                            class="bg-cuotas-ink inline-flex rounded-full px-5 py-2.5 text-[13px] font-semibold text-white transition-transform duration-200 hover:scale-[1.03] active:scale-[0.97]"
                        >
                            {{ t('landing.create_account') }}
                        </Link>
                        <Link
                            :href="login()"
                            class="border-cuotas-ink inline-flex rounded-full border-[1.5px] px-5 py-2.5 text-[13px] font-semibold transition-transform duration-200 hover:scale-[1.03] active:scale-[0.97]"
                        >
                            {{ t('landing.have_account') }}
                        </Link>
                    </div>
                </div>

                <div
                    class="relative flex min-h-[320px] items-center justify-center"
                >
                    <div
                        class="bg-cuotas-bg-deep/30 absolute h-[280px] w-[280px] rounded-full blur-2xl"
                    />
                    <div
                        class="border-cuotas-ink relative z-10 w-[290px] rounded-2xl border-2 bg-white p-3.5 shadow-[8px_8px_0_rgba(22,22,22,0.08)]"
                    >
                        <div class="mb-3 flex gap-1">
                            <i
                                class="border-cuotas-ink h-2 w-2 rounded-full border"
                            />
                            <i
                                class="border-cuotas-ink h-2 w-2 rounded-full border"
                            />
                            <i
                                class="border-cuotas-ink h-2 w-2 rounded-full border"
                            />
                        </div>
                        <p
                            class="font-[family-name:var(--font-head)] text-[26px] font-bold"
                        >
                            $ 30.093
                        </p>
                        <span
                            class="bg-cuotas-bg mt-1 inline-block h-1.5 w-10 rounded"
                        />
                        <div
                            class="border-cuotas-border mt-2 flex justify-between border-t border-dashed py-2 text-xs font-semibold"
                        >
                            <span>Fotostanisic</span><span>$ 3.936</span>
                        </div>
                        <div
                            class="border-cuotas-border flex justify-between border-t border-dashed py-2 text-xs font-semibold"
                        >
                            <span>Wikimusculos</span><span>$ 1.660</span>
                        </div>
                    </div>
                </div>
            </section>

            <div
                class="bg-cuotas-ink mb-14 flex flex-wrap items-center justify-between gap-3 rounded-full px-6 py-3.5 text-sm text-white"
            >
                <span>{{ t('landing.banner') }}</span>
                <span class="text-white/85">{{ t('landing.web_version') }}</span>
            </div>

            <footer
                class="border-cuotas-border text-cuotas-muted flex flex-wrap items-center justify-between gap-3 border-t pt-7 text-sm"
            >
                <span>{{ t('landing.copyright') }}</span>
                <div class="flex gap-4">
                    <Link :href="pricing()" class="hover:text-cuotas-ink">{{
                        t('nav.pricing')
                    }}</Link>
                    <Link :href="terms()" class="hover:text-cuotas-ink">{{
                        t('nav.terms')
                    }}</Link>
                    <Link :href="privacy()" class="hover:text-cuotas-ink">{{
                        t('nav.privacy')
                    }}</Link>
                </div>
            </footer>
        </div>
    </div>
</template>
