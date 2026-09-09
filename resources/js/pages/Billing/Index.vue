<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { useTrans } from '@/composables/useTrans';
import { cancel, resume } from '@/routes/billing';
import { show as perfil } from '@/routes/perfil';
import { pricing } from '@/routes';

defineOptions({
    layout: {
        active: 'profile',
    },
});

defineProps<{
    plan: {
        name: string;
        price: string;
        interval: string;
        status_label: string;
        renews_at: string | null;
        on_grace_period: boolean;
        is_free: boolean;
        provider: string | null;
    };
    stats: {
        next_charge: string | null;
        paid_this_year: string;
        payment_count: number;
        member_since: string;
    };
    paymentMethod: {
        brand: string;
        last_four: string;
        expires: string;
    } | null;
    invoices: {
        id: string;
        date: string;
        description: string;
        amount: string;
        status: string;
        url: string | null;
    }[];
    checkoutSuccess: boolean;
}>();

const { t } = useTrans();
</script>

<template>
    <Head :title="t('billing.title')" />

    <Link
        :href="perfil()"
        class="text-cuotas-muted hover:text-cuotas-ink mb-5 inline-flex text-[13px]"
        >{{ t('billing.back') }}</Link
    >

    <p class="text-cuotas-muted mb-2 text-[13px] font-medium">
        {{ t('billing.account') }}
    </p>
    <h1 class="mb-1.5 text-3xl font-semibold">{{ t('billing.title') }}</h1>
    <p class="text-cuotas-muted mb-7 max-w-[52ch] text-sm leading-relaxed">
        {{ t('billing.lead') }}
    </p>

    <p
        v-if="checkoutSuccess"
        class="border-cuotas-green text-cuotas-green mb-6 rounded-[var(--radius-cuotas-md)] border bg-[#e7f5ee] px-4 py-3 text-sm"
    >
        {{ t('billing.activated') }}
    </p>

    <div
        class="bg-cuotas-ink mb-8 flex flex-wrap items-center justify-between gap-4 rounded-[var(--radius-cuotas-md)] p-6 text-white"
    >
        <div>
            <p
                class="flex items-center gap-2 font-[family-name:var(--font-head)] text-[22px] font-bold"
            >
                {{ t('billing.plan', { name: plan.name }) }}
                <span
                    class="bg-cuotas-bg text-cuotas-ink rounded-full px-2 py-0.5 text-[10px] font-bold"
                    >{{ plan.status_label }}</span
                >
            </p>
            <p class="mt-1 text-xs text-[#b9b9b9]">
                {{
                    plan.on_grace_period
                        ? t('billing.grace_until', {
                              date: plan.renews_at ?? '',
                          })
                        : plan.renews_at
                          ? t('billing.renews_on', { date: plan.renews_at })
                          : t('billing.no_auto_renewal')
                }}
            </p>
            <p v-if="plan.provider" class="mt-1 text-[11px] text-[#b9b9b9]">
                {{ t('billing.charged_by', { provider: plan.provider }) }}
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <div class="text-right">
                <p
                    class="font-[family-name:var(--font-head)] text-xl font-bold"
                >
                    $ {{ plan.price }}
                </p>
                <p class="text-[11px] text-[#b9b9b9]">
                    /
                    {{
                        plan.interval === 'year'
                            ? t('billing.year')
                            : t('billing.month')
                    }}
                </p>
            </div>
            <Link
                :href="pricing()"
                class="rounded-full border border-white px-4 py-2 text-[13px] font-semibold"
            >
                {{ t('common.see_plans') }}
            </Link>
            <Form
                v-if="plan.on_grace_period"
                v-bind="resume.form()"
                #default="{ processing }"
            >
                <button
                    type="submit"
                    :disabled="processing"
                    class="bg-cuotas-bg text-cuotas-ink rounded-full px-4 py-2 text-[13px] font-semibold disabled:opacity-60"
                >
                    {{ t('billing.resume') }}
                </button>
            </Form>
            <Form
                v-else-if="!plan.is_free"
                v-bind="cancel.form()"
                #default="{ processing }"
            >
                <button
                    type="submit"
                    :disabled="processing"
                    class="rounded-full border border-white/40 px-4 py-2 text-[13px] font-semibold disabled:opacity-60"
                >
                    {{ t('common.cancel') }}
                </button>
            </Form>
        </div>
    </div>

    <div class="mb-9 grid gap-3 sm:grid-cols-3">
        <div
            class="border-cuotas-border bg-cuotas-surface-2 rounded-[var(--radius-cuotas-md)] border p-4"
        >
            <p class="text-cuotas-muted text-xs">
                {{ t('billing.next_charge') }}
            </p>
            <p class="font-[family-name:var(--font-head)] text-xl font-bold">
                {{ stats.next_charge ?? '—' }}
            </p>
            <p class="text-cuotas-muted text-xs">
                {{
                    plan.is_free
                        ? t('common.free')
                        : t('billing.plan', { name: plan.name })
                }}
            </p>
        </div>
        <div
            class="border-cuotas-border bg-cuotas-surface-2 rounded-[var(--radius-cuotas-md)] border p-4"
        >
            <p class="text-cuotas-muted text-xs">
                {{ t('billing.paid_year') }}
            </p>
            <p class="font-[family-name:var(--font-head)] text-xl font-bold">
                $ {{ stats.paid_this_year }}
            </p>
            <p class="text-cuotas-muted text-xs">
                {{ stats.payment_count }} pagos
            </p>
        </div>
        <div
            class="border-cuotas-border bg-cuotas-surface-2 rounded-[var(--radius-cuotas-md)] border p-4"
        >
            <p class="text-cuotas-muted text-xs">
                {{ t('billing.member_since') }}
            </p>
            <p class="font-[family-name:var(--font-head)] text-xl font-bold">
                {{ stats.member_since }}
            </p>
        </div>
    </div>

    <div class="mb-3.5 flex items-baseline justify-between">
        <h2 class="text-[19px] font-semibold">
            {{ t('billing.payment_method') }}
        </h2>
    </div>
    <div
        class="border-cuotas-border bg-cuotas-surface-2 mb-8 flex items-center justify-between rounded-[var(--radius-cuotas-md)] border px-4 py-4"
    >
        <p v-if="!paymentMethod" class="text-cuotas-muted text-sm">
            {{ t('billing.no_method') }}
        </p>
        <div v-else class="flex items-center gap-3.5">
            <div
                class="border-cuotas-border text-cuotas-muted flex h-[30px] w-11 items-center justify-center rounded-md border bg-white text-[9px] font-bold"
            >
                {{ paymentMethod.brand }}
            </div>
            <div>
                <p class="text-sm font-semibold">
                    ···· {{ paymentMethod.last_four }}
                </p>
                <p class="text-cuotas-muted text-xs">
                    {{
                        t('billing.expires', {
                            date: paymentMethod.expires,
                        })
                    }}
                </p>
            </div>
        </div>
        <Link
            :href="pricing()"
            class="bg-cuotas-surface-2 rounded-full px-3 py-1.5 text-xs font-semibold"
        >
            {{ t('common.see_plans') }}
        </Link>
    </div>

    <div class="mb-3.5 flex items-baseline justify-between">
        <h2 class="text-[19px] font-semibold">{{ t('billing.invoices') }}</h2>
        <span class="text-cuotas-muted text-xs">{{
            t('billing.invoices_count', { count: invoices.length })
        }}</span>
    </div>
    <div
        class="border-cuotas-border overflow-hidden rounded-[var(--radius-cuotas-md)] border"
    >
        <p
            v-if="invoices.length === 0"
            class="text-cuotas-muted bg-white px-4 py-8 text-center text-sm"
        >
            {{ t('billing.no_invoices') }}
        </p>
        <div v-else>
            <div
                class="bg-cuotas-surface-2 text-cuotas-muted hidden px-4 py-3 text-[11px] font-semibold tracking-wide uppercase sm:flex"
            >
                <span class="w-[26%]">{{ t('billing.date') }}</span>
                <span class="w-[30%]">{{ t('billing.description') }}</span>
                <span class="w-[16%]">{{ t('billing.amount') }}</span>
                <span class="w-[14%]">{{ t('billing.status') }}</span>
                <span class="w-[14%]" />
            </div>
            <div
                v-for="invoice in invoices"
                :key="invoice.id"
                class="border-cuotas-border flex items-center border-t bg-white px-4 py-3.5 text-[13px]"
            >
                <span class="w-[26%]">{{ invoice.date }}</span>
                <span class="text-cuotas-muted w-[30%]">{{
                    invoice.description
                }}</span>
                <span
                    class="w-[16%] font-[family-name:var(--font-head)] font-semibold"
                    >$ {{ invoice.amount }}</span
                >
                <span class="w-[14%]">
                    <span
                        class="text-cuotas-green rounded-full bg-[#e7f5ee] px-2.5 py-0.5 text-[11px] font-semibold"
                        >{{ t('billing.paid') }}</span
                    >
                </span>
                <span class="w-[14%] text-right">
                    <a
                        v-if="invoice.url"
                        :href="invoice.url"
                        target="_blank"
                        rel="noreferrer"
                        class="text-cuotas-ink text-xs font-semibold"
                    >
                        {{ t('billing.view') }}
                    </a>
                </span>
            </div>
        </div>
    </div>
</template>
