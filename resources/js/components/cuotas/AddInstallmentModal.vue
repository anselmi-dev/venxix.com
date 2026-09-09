<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import CardCarouselSelect, {
    type PurchaseCard,
} from '@/components/cuotas/CardCarouselSelect.vue';
import DatePicker from '@/components/cuotas/DatePicker.vue';
import InputError from '@/components/InputError.vue';
import MoneyInput from '@/components/cuotas/MoneyInput.vue';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { useTrans } from '@/composables/useTrans';
import { store as storePurchase } from '@/routes/purchases';

export type { PurchaseCard };

const props = withDefaults(
    defineProps<{
        cardId?: number;
        cards?: PurchaseCard[];
        title?: string;
    }>(),
    {
        cardId: undefined,
    },
);

const { t } = useTrans();

const modalTitle = computed(() => props.title ?? t('purchase.title'));

const open = defineModel<boolean>('open', { default: false });
const currency = ref('UYU');
const selectedCardId = ref<number | null>(
    props.cardId ?? props.cards?.[0]?.id ?? null,
);
const hasCardSelector = computed(() => props.cards !== undefined);

watch(open, (isOpen) => {
    if (
        isOpen &&
        !props.cardId &&
        !props.cards?.some((card) => card.id === selectedCardId.value)
    ) {
        selectedCardId.value = props.cards?.[0]?.id ?? null;
    }
});
</script>

<template>
    <Dialog :open="open" @update:open="open = $event">
        <DialogContent
            class="max-h-[calc(100dvh-2rem)] overflow-y-auto rounded-[var(--radius-cuotas-lg)] sm:max-w-2xl"
        >
            <DialogHeader>
                <DialogTitle>{{ modalTitle }}</DialogTitle>
            </DialogHeader>

            <Form
                v-bind="storePurchase.form()"
                v-slot="{ errors, processing }"
                class="grid gap-3.5"
                @success="open = false"
            >
                <input
                    v-if="!hasCardSelector"
                    type="hidden"
                    name="card_id"
                    :value="cardId"
                />

                <CardCarouselSelect
                    v-else
                    v-model="selectedCardId"
                    :cards="cards ?? []"
                    :label="t('purchase.card')"
                    :error="errors.card_id"
                />

                <div class="grid gap-1.5">
                    <label
                        for="add-installment-merchant"
                        class="text-cuotas-muted text-xs font-medium"
                        >{{ t('purchase.merchant') }}</label
                    >
                    <input
                        id="add-installment-merchant"
                        name="merchant"
                        required
                        autofocus
                        :placeholder="t('purchase.merchant_placeholder')"
                        class="border-cuotas-border bg-cuotas-surface-2 w-full rounded-[var(--radius-cuotas-sm)] border-[1.5px] px-3.5 py-3 text-sm"
                    />
                    <InputError :message="errors.merchant" />
                </div>

                <div class="grid grid-cols-2 gap-3.5">
                    <div class="grid gap-1.5">
                        <label
                            for="add-installment-amount"
                            class="text-cuotas-muted text-xs font-medium"
                            >{{ t('purchase.amount') }}</label
                        >
                        <MoneyInput
                            id="add-installment-amount"
                            name="total_amount"
                            :currency="currency"
                            required
                        />
                        <InputError :message="errors.total_amount" />
                    </div>

                    <div class="grid gap-1.5">
                        <label
                            for="add-installment-currency"
                            class="text-cuotas-muted text-xs font-medium"
                            >{{ t('purchase.currency') }}</label
                        >
                        <select
                            id="add-installment-currency"
                            name="currency"
                            v-model="currency"
                            required
                            class="border-cuotas-border bg-cuotas-surface-2 w-full rounded-[var(--radius-cuotas-sm)] border-[1.5px] px-3.5 py-3 text-sm"
                        >
                            <option value="UYU">UYU</option>
                            <option value="USD">USD</option>
                        </select>
                        <InputError :message="errors.currency" />
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3.5">
                    <div class="grid gap-1.5">
                        <label
                            for="add-installment-count"
                            class="text-cuotas-muted text-xs font-medium"
                            >{{ t('purchase.count') }}</label
                        >
                        <input
                            id="add-installment-count"
                            name="installment_count"
                            type="number"
                            min="1"
                            max="48"
                            required
                            class="border-cuotas-border bg-cuotas-surface-2 w-full rounded-[var(--radius-cuotas-sm)] border-[1.5px] px-3.5 py-3 text-sm"
                        />
                        <InputError :message="errors.installment_count" />
                    </div>

                    <div class="grid gap-1.5">
                        <label
                            for="add-installment-due-date"
                            class="text-cuotas-muted text-xs font-medium"
                            >{{ t('purchase.first_due') }}</label
                        >
                        <DatePicker
                            id="add-installment-due-date"
                            name="first_due_date"
                            required
                        />
                        <InputError :message="errors.first_due_date" />
                    </div>
                </div>

                <button
                    type="submit"
                    :disabled="
                        processing || (hasCardSelector && !selectedCardId)
                    "
                    class="bg-cuotas-ink mt-1.5 w-full cursor-pointer rounded-full px-5 py-3 text-sm font-semibold text-white transition-transform duration-200 hover:scale-[1.01] active:scale-[0.98] disabled:cursor-not-allowed disabled:opacity-60"
                >
                    {{ t('purchase.submit') }}
                </button>
            </Form>
        </DialogContent>
    </Dialog>
</template>
