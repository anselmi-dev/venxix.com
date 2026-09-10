<script setup lang="ts">
import { Form, Head, Link, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { useTrans } from '@/composables/useTrans';
import { store as checkout } from '@/routes/checkout';
import { register } from '@/routes';

defineOptions({
    layout: {
        active: 'pricing',
        variant: 'marketing',
    },
});

const props = defineProps<{
    authenticated: boolean;
    currentFamily: string | null;
    prices: {
        free: { monthly: number; yearly: number };
        pro: { monthly: number; yearly: number };
        familiar: { monthly: number; yearly: number };
    };
    paymentGateways: {
        slug: 'stripe' | 'mercadopago' | 'dlocalgo';
        name: string;
        configured: boolean;
    }[];
    defaultGateway: 'stripe' | 'mercadopago' | 'dlocalgo';
}>();

const page = usePage();
const { t } = useTrans();

const yearly = ref(false);
const paymentGateway = ref<'stripe' | 'mercadopago' | 'dlocalgo'>(
    props.paymentGateways.some(
        (gateway) => gateway.slug === props.defaultGateway,
    )
        ? props.defaultGateway
        : (props.paymentGateways[0]?.slug ?? 'stripe'),
);

const plans = computed(() => [
    {
        slug: 'free' as const,
        name: t('pricing.free.name'),
        description: t('pricing.free.description'),
        monthly: 0,
        featured: false,
        free: true,
        billed: t('pricing.free.billed'),
        cta: t('pricing.free.cta'),
        features: [
            { text: t('pricing.free.f1'), included: true },
            { text: t('pricing.free.f2'), included: true },
            { text: t('pricing.free.f3'), included: true },
            { text: t('pricing.free.f4'), included: false },
            { text: t('pricing.free.f5'), included: false },
            { text: t('pricing.free.f6'), included: false },
        ],
    },
    {
        slug: 'pro' as const,
        name: t('pricing.pro.name'),
        description: t('pricing.pro.description'),
        monthly: 199,
        featured: true,
        free: false,
        billedMonthly: t('pricing.pro.billed_monthly'),
        billedYearly: t('pricing.pro.billed_yearly'),
        cta: t('pricing.pro.cta'),
        features: [
            { text: t('pricing.pro.f1'), included: true },
            { text: t('pricing.pro.f2'), included: true },
            { text: t('pricing.pro.f3'), included: true },
            { text: t('pricing.pro.f4'), included: true },
            { text: t('pricing.pro.f5'), included: true },
            { text: t('pricing.pro.f6'), included: true },
            { text: t('pricing.pro.f7'), included: true },
        ],
    },
    {
        slug: 'familiar' as const,
        name: t('pricing.familiar.name'),
        description: t('pricing.familiar.description'),
        monthly: 299,
        featured: false,
        free: false,
        billedMonthly: t('pricing.familiar.billed_monthly'),
        billedYearly: t('pricing.familiar.billed_yearly'),
        cta: t('pricing.familiar.cta'),
        features: [
            { text: t('pricing.familiar.f1'), included: true },
            { text: t('pricing.familiar.f2'), included: true },
            { text: t('pricing.familiar.f3'), included: true },
            { text: t('pricing.familiar.f4'), included: true },
        ],
    },
]);

function price(slug: 'free' | 'pro' | 'familiar'): string {
    const amount = yearly.value
        ? props.prices[slug].yearly
        : props.prices[slug].monthly;
    const locale = page.props.locale === 'en' ? 'en-US' : 'es-UY';

    return `$ ${amount.toLocaleString(locale)}`;
}

function perLabel(slug: 'free' | 'pro' | 'familiar'): string {
    if (slug === 'free') {
        return t('common.per_month');
    }

    return yearly.value ? t('common.per_year') : t('common.per_month');
}
</script>

<template>
    <Head :title="t('pricing.title')" />

    <section class="mx-auto mb-5 max-w-[560px] text-center">
        <p class="text-cuotas-muted mb-2.5 text-[13px] font-medium">
            {{ t('pricing.title') }}
        </p>
        <h1 class="mb-2.5 text-[38px] leading-tight">
            {{ t('pricing.heading') }}
        </h1>
        <p class="text-cuotas-muted text-[15px] leading-relaxed">
            {{ t('pricing.lead') }}
        </p>
    </section>

    <div class="mb-10 flex justify-center">
        <div
            class="border-cuotas-border bg-cuotas-surface-2 relative flex rounded-full border p-1.5"
        >
            <span
                aria-hidden="true"
                class="bg-cuotas-ink absolute inset-y-1.5 left-1.5 w-[calc(50%-12px)] rounded-full transition-transform duration-300 ease-out"
                :class="yearly ? 'translate-x-full' : 'translate-x-0'"
            />
            <button
                type="button"
                class="relative z-10 flex-1 cursor-pointer rounded-full px-5 py-2 text-center text-[13px] font-semibold whitespace-nowrap transition-colors duration-200"
                :class="!yearly ? 'text-white' : 'text-cuotas-muted'"
                @click="yearly = false"
            >
                {{ t('pricing.monthly') }}
            </button>
            <button
                type="button"
                class="relative z-10 flex flex-1 cursor-pointer items-center justify-center gap-1.5 rounded-full px-5 py-2 text-center text-[13px] font-semibold whitespace-nowrap transition-colors duration-200"
                :class="yearly ? 'text-white' : 'text-cuotas-muted'"
                @click="yearly = true"
            >
                {{ t('pricing.yearly') }}
                <span
                    class="bg-cuotas-red rounded px-1.5 py-0.5 text-[10px] font-bold text-white"
                    >-20%</span
                >
            </button>
        </div>
    </div>

    <div
        v-if="authenticated && paymentGateways.length > 1"
        class="mb-8 flex flex-wrap items-center justify-center gap-2"
    >
        <span class="text-cuotas-muted mr-1 text-xs">{{
            t('pricing.pay_with')
        }}</span>
        <button
            v-for="gateway in paymentGateways"
            :key="gateway.slug"
            type="button"
            class="cursor-pointer rounded-full border px-4 py-2 text-xs font-semibold transition-colors duration-200"
            :class="
                paymentGateway === gateway.slug
                    ? 'border-cuotas-ink bg-cuotas-ink text-white'
                    : 'border-cuotas-border text-cuotas-muted bg-white'
            "
            @click="paymentGateway = gateway.slug"
        >
            {{ gateway.name }}
        </button>
    </div>

    <div class="mb-14 grid items-stretch gap-4 lg:grid-cols-3">
        <div
            v-for="plan in plans"
            :key="plan.slug"
            class="relative flex flex-col rounded-[var(--radius-cuotas-lg)] border-[1.5px] p-7 transition-transform duration-300 ease-out hover:z-10 hover:scale-[1.05]"
            :class="
                plan.featured
                    ? 'border-cuotas-ink bg-cuotas-ink text-white'
                    : 'border-cuotas-border bg-cuotas-surface-2'
            "
        >
            <span
                v-if="plan.featured"
                class="bg-cuotas-bg text-cuotas-ink absolute -top-3.5 left-7 rounded-full px-3 py-1 text-[11px] font-bold"
            >
                {{ t('pricing.most_chosen') }}
            </span>
            <p
                class="mb-1.5 font-[family-name:var(--font-head)] text-lg font-semibold"
            >
                {{ plan.name }}
            </p>
            <p
                class="mb-5 text-[13px]"
                :class="plan.featured ? 'text-[#b9b9b9]' : 'text-cuotas-muted'"
            >
                {{ plan.description }}
            </p>
            <div class="mb-1 flex items-baseline gap-1.5">
                <span
                    class="font-[family-name:var(--font-head)] text-[38px] font-bold"
                    >{{ price(plan.slug) }}</span
                >
                <span
                    class="text-[13px]"
                    :class="
                        plan.featured ? 'text-[#b9b9b9]' : 'text-cuotas-muted'
                    "
                    >{{ perLabel(plan.slug) }}</span
                >
            </div>
            <p
                class="mb-6 text-xs"
                :class="plan.featured ? 'text-[#b9b9b9]' : 'text-cuotas-muted'"
            >
                {{
                    plan.free
                        ? plan.billed
                        : yearly
                          ? plan.billedYearly
                          : plan.billedMonthly
                }}
            </p>
            <ul class="mb-7 flex flex-1 flex-col gap-2.5 text-[13px]">
                <li
                    v-for="(feature, index) in plan.features"
                    :key="index"
                    class="flex items-start gap-2.5"
                    :class="{ 'opacity-40': !feature.included }"
                >
                    <span
                        class="mt-0.5 flex h-4 w-4 shrink-0 items-center justify-center rounded-full border-[1.5px] text-[9px]"
                        :class="
                            plan.featured
                                ? 'border-cuotas-bg bg-cuotas-bg text-cuotas-ink'
                                : 'border-cuotas-border bg-white'
                        "
                    >
                        {{ feature.included ? '✓' : '–' }}
                    </span>
                    {{ feature.text }}
                </li>
            </ul>
            <Form
                v-if="authenticated"
                v-bind="checkout.form({ plan: plan.slug })"
            >
                <input
                    type="hidden"
                    name="interval"
                    :value="yearly ? 'yearly' : 'monthly'"
                />
                <input type="hidden" name="gateway" :value="paymentGateway" />
                <button
                    type="submit"
                    :disabled="
                        currentFamily === plan.slug ||
                        (!plan.free && paymentGateways.length === 0)
                    "
                    class="inline-flex w-full cursor-pointer items-center justify-center rounded-full px-5 py-2.5 text-[13px] font-semibold disabled:cursor-not-allowed disabled:opacity-70"
                    :class="
                        plan.featured
                            ? 'bg-cuotas-bg text-cuotas-ink'
                            : plan.free
                              ? 'border-cuotas-ink text-cuotas-ink border-[1.5px] bg-transparent'
                              : 'bg-cuotas-ink text-white'
                    "
                >
                    {{
                        currentFamily === plan.slug
                            ? t('pricing.current_plan')
                            : !plan.free && paymentGateways.length === 0
                              ? t('pricing.coming_soon')
                              : plan.free
                                ? t('pricing.switch_free')
                                : plan.cta
                    }}
                </button>
            </Form>
            <Link
                v-else
                :href="register()"
                class="inline-flex w-full items-center justify-center rounded-full px-5 py-2.5 text-[13px] font-semibold"
                :class="
                    plan.featured
                        ? 'bg-cuotas-bg text-cuotas-ink'
                        : plan.free
                          ? 'border-cuotas-ink text-cuotas-ink border-[1.5px] bg-transparent'
                          : 'bg-cuotas-ink text-white'
                "
            >
                {{ plan.cta }}
            </Link>
        </div>
    </div>

    <section>
        <p class="text-cuotas-muted mb-4 text-[13px]">{{ t('pricing.faq') }}</p>
        <div class="border-cuotas-border border-t py-5">
            <h3 class="mb-2 text-base font-semibold">
                {{ t('pricing.faq_cancel_q') }}
            </h3>
            <p
                class="text-cuotas-muted max-w-[60ch] text-[13px] leading-relaxed"
            >
                {{ t('pricing.faq_cancel_a') }}
            </p>
        </div>
        <div class="border-cuotas-border border-t py-5">
            <h3 class="mb-2 text-base font-semibold">
                {{ t('pricing.faq_data_q') }}
            </h3>
            <p
                class="text-cuotas-muted max-w-[60ch] text-[13px] leading-relaxed"
            >
                {{ t('pricing.faq_data_a') }}
            </p>
        </div>
        <div class="border-cuotas-border border-t py-5">
            <h3 class="mb-2 text-base font-semibold">
                {{ t('pricing.faq_currency_q') }}
            </h3>
            <p
                class="text-cuotas-muted max-w-[60ch] text-[13px] leading-relaxed"
            >
                {{ t('pricing.faq_currency_a') }}
            </p>
        </div>
        <div class="border-cuotas-border border-t border-b py-5">
            <h3 class="mb-2 text-base font-semibold">
                {{ t('pricing.faq_students_q') }}
            </h3>
            <p
                class="text-cuotas-muted max-w-[60ch] text-[13px] leading-relaxed"
            >
                {{ t('pricing.faq_students_a') }}
            </p>
        </div>
    </section>
</template>
