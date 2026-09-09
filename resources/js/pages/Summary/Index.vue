<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import CuotasProjection, {
    type Projection,
} from '@/components/cuotas/CuotasProjection.vue';
import { useTrans } from '@/composables/useTrans';
import { show as showCard } from '@/routes/cards';
import { panel, summary } from '@/routes';

defineOptions({
    layout: {
        active: 'summary',
    },
});

const props = defineProps<{
    cards: {
        id: number;
        ulid: string;
        display_name: string;
        last_four: string;
    }[];
    selectedCardId: number | null;
    selectedCardUlid: string | null;
    projection: Projection;
    showBothCurrencies: boolean;
    preferredCurrency: string;
    exchangeRate: string | null;
    canExport: boolean;
}>();

const { t } = useTrans();

function summaryHref(cardId: number | null): string {
    if (cardId === null) {
        return summary.url();
    }

    return summary.url({ query: { card: cardId } });
}

function exportHref(): string {
    const params = new URLSearchParams({ status: 'pending' });

    if (props.selectedCardId !== null) {
        params.set('card', String(props.selectedCardId));
    }

    return `/exportar/cuotas?${params.toString()}`;
}
</script>

<template>
    <Head :title="t('summary.title')" />

    <Link
        :href="panel()"
        class="text-cuotas-muted hover:text-cuotas-ink mb-5 inline-flex text-[13px]"
        >{{ t('summary.back_panel') }}</Link
    >

    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div>
            <p class="text-cuotas-muted mb-2 text-[13px] font-medium">
                {{ t('summary.kicker') }}
            </p>
            <h1 class="text-3xl font-semibold">{{ t('summary.heading') }}</h1>
        </div>
        <a
            v-if="canExport && cards.length > 0"
            :href="exportHref()"
            class="border-cuotas-ink rounded-full border px-4 py-2 text-sm font-semibold"
        >
            {{ t('summary.export') }}
        </a>
    </div>

    <div v-if="cards.length > 1" class="mb-6 flex flex-wrap gap-2">
        <Link
            :href="summaryHref(null)"
            class="rounded-full border px-3 py-1.5 text-xs font-semibold"
            :class="
                selectedCardId === null
                    ? 'border-cuotas-ink bg-cuotas-ink text-white'
                    : 'border-cuotas-border text-cuotas-muted bg-white'
            "
        >
            {{ t('summary.all_cards') }}
        </Link>
        <Link
            v-for="card in cards"
            :key="card.id"
            :href="summaryHref(card.id)"
            class="rounded-full border px-3 py-1.5 text-xs font-semibold"
            :class="
                selectedCardId === card.id
                    ? 'border-cuotas-ink bg-cuotas-ink text-white'
                    : 'border-cuotas-border text-cuotas-muted bg-white'
            "
        >
            {{ card.display_name }}
        </Link>
    </div>

    <div
        v-if="cards.length === 0"
        class="border-cuotas-border rounded-[var(--radius-cuotas-md)] border border-dashed p-10 text-center"
    >
        <p class="mb-1 text-sm font-semibold">
            {{ t('projection.empty_title') }}
        </p>
        <p class="text-cuotas-muted mb-3 text-sm">
            {{ t('projection.empty_body') }}
        </p>
        <Link
            :href="panel()"
            class="bg-cuotas-ink inline-flex rounded-full px-4 py-2 text-sm font-semibold text-white"
        >
            {{ t('nav.go_panel') }}
        </Link>
    </div>

    <template v-else>
        <CuotasProjection
            :projection="projection"
            :show-both-currencies="showBothCurrencies"
            :preferred-currency="preferredCurrency"
            :exchange-rate="exchangeRate"
        />

        <div v-if="selectedCardId" class="mt-6">
            <Link
                v-if="selectedCardUlid"
                :href="showCard(selectedCardUlid)"
                class="border-cuotas-ink rounded-full border px-4 py-2 text-sm font-semibold"
            >
                {{ t('panel.see_summary') }}
            </Link>
        </div>
    </template>
</template>
