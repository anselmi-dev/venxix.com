<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Pencil, Plus, Upload } from '@animated-color-icons/lucide-vue';
import AddInstallmentModal from '@/components/cuotas/AddInstallmentModal.vue';
import CuotasProjection, {
    type Projection,
} from '@/components/cuotas/CuotasProjection.vue';
import { formatMoney } from '@/composables/useCurrencyDisplay';
import { useTrans } from '@/composables/useTrans';
import { create as createImport } from '@/routes/imports';
import { edit } from '@/routes/cards';
import { panel } from '@/routes';

defineOptions({
    layout: {
        active: 'summary',
    },
});

const props = defineProps<{
    card: {
        id: number;
        ulid: string;
        display_name: string;
        bank: string;
        last_four: string;
        type_label: string;
        closing_day: number | null;
        credit_limit: string | null;
        credit_limit_currency: string | null;
    };
    projection: Projection;
    showBothCurrencies: boolean;
    preferredCurrency: string;
    exchangeRate: string | null;
    canImportCsv: boolean;
}>();

const { t } = useTrans();
const showAddModal = ref(false);
</script>

<template>
    <Head :title="t('card_show.title', { name: props.card.display_name })" />

    <Link
        :href="panel()"
        class="text-cuotas-muted hover:text-cuotas-ink mb-5 inline-flex text-[13px]"
        >{{ t('summary.back_panel') }}</Link
    >

    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div>
            <p class="text-cuotas-muted mb-2 text-[13px] font-medium">
                {{ card.bank }} · {{ card.type_label }}
            </p>
            <h1 class="text-3xl font-semibold">
                {{
                    t('card_show.heading', {
                        last_four: card.last_four,
                    })
                }}
            </h1>
            <p
                v-if="card.closing_day || card.credit_limit"
                class="text-cuotas-muted mt-2 flex flex-wrap gap-x-3 gap-y-1 text-[13px]"
            >
                <span v-if="card.closing_day">
                    {{ t('panel.closing_badge', { day: card.closing_day }) }}
                </span>
                <span v-if="card.credit_limit">
                    {{
                        t('panel.credit_limit_badge', {
                            amount: formatMoney(
                                card.credit_limit,
                                card.credit_limit_currency ?? 'UYU',
                            ),
                        })
                    }}
                </span>
            </p>
        </div>
        <button
            type="button"
            class="al-icon-wrapper bg-cuotas-ink inline-flex shrink-0 cursor-pointer items-center gap-1.5 rounded-full px-4 py-2.5 text-sm font-semibold text-white transition-transform duration-200 hover:scale-[1.02] active:scale-[0.98]"
            @click="showAddModal = true"
        >
            <Plus class="h-4 w-4" />
            {{ t('card_show.add') }}
        </button>
    </div>

    <AddInstallmentModal v-model:open="showAddModal" :card-id="card.id" />

    <CuotasProjection
        :projection="projection"
        :show-both-currencies="showBothCurrencies"
        :preferred-currency="preferredCurrency"
        :exchange-rate="exchangeRate"
    />

    <div class="mt-6 flex flex-wrap gap-2">
        <Link
            v-if="canImportCsv"
            :href="createImport({ query: { card: card.id } })"
            class="al-icon-wrapper bg-cuotas-ink inline-flex items-center gap-1.5 rounded-full px-4 py-2 text-sm font-semibold text-white"
        >
            <Upload class="h-4 w-4" />
            {{ t('panel.import_csv') }}
        </Link>
        <Link
            :href="edit(card.ulid)"
            class="al-icon-wrapper border-cuotas-ink inline-flex items-center gap-1.5 rounded-full border px-4 py-2 text-sm font-semibold"
        >
            <Pencil class="h-4 w-4" />
            {{ t('card_show.edit') }}
        </Link>
    </div>
</template>
