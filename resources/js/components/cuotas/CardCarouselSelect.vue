<script setup lang="ts">
import { ChevronLeft, ChevronRight } from '@animated-color-icons/lucide-vue';
import { useTemplateRef } from 'vue';
import InputError from '@/components/InputError.vue';
import { useTrans } from '@/composables/useTrans';

export type PurchaseCard = {
    id: number;
    bank: string;
    last_four: string;
    alias: string | null;
};

const props = withDefaults(
    defineProps<{
        cards: PurchaseCard[];
        label: string;
        name?: string;
        required?: boolean;
        error?: string;
    }>(),
    {
        name: 'card_id',
        required: true,
    },
);

const modelValue = defineModel<number | null>({ default: null });

const { t } = useTrans();

const cardCarousel = useTemplateRef<HTMLDivElement>('cardCarousel');

function scrollCards(direction: 1 | -1): void {
    const carousel = cardCarousel.value;

    if (!carousel) {
        return;
    }

    const card = carousel.querySelector<HTMLElement>('[data-purchase-card]');
    const step = card ? card.offsetWidth + 12 : carousel.clientWidth * 0.8;

    carousel.scrollBy({ left: step * direction, behavior: 'smooth' });
}
</script>

<template>
    <div class="grid gap-2">
        <div class="flex items-center justify-between gap-3">
            <span class="text-cuotas-muted text-xs font-medium">
                {{ label }}
            </span>
            <div v-if="cards.length > 1" class="flex items-center gap-1.5">
                <button
                    type="button"
                    :aria-label="t('purchase.prev_cards')"
                    class="al-icon-wrapper border-cuotas-border hover:bg-cuotas-surface-2 flex h-7 w-7 cursor-pointer items-center justify-center rounded-full border bg-white"
                    @click="scrollCards(-1)"
                >
                    <ChevronLeft class="h-4 w-4" />
                </button>
                <button
                    type="button"
                    :aria-label="t('purchase.next_cards')"
                    class="al-icon-wrapper border-cuotas-border hover:bg-cuotas-surface-2 flex h-7 w-7 cursor-pointer items-center justify-center rounded-full border bg-white"
                    @click="scrollCards(1)"
                >
                    <ChevronRight class="h-4 w-4" />
                </button>
            </div>
        </div>

        <div
            ref="cardCarousel"
            role="radiogroup"
            :aria-label="t('purchase.select_card')"
            class="card-carousel flex snap-x snap-mandatory gap-3 overflow-x-auto scroll-smooth pb-1"
        >
            <p v-if="cards.length === 0" class="text-cuotas-muted py-4 text-sm">
                {{ t('purchase.no_cards') }}
            </p>
            <label
                v-for="(card, index) in cards"
                :key="card.id"
                data-purchase-card
                class="border-cuotas-border bg-cuotas-surface-2 focus-within:ring-cuotas-bg-deep relative flex w-[210px] shrink-0 cursor-pointer snap-start flex-col gap-5 rounded-[var(--radius-cuotas-md)] border-[1.5px] p-4 transition duration-200 outline-none focus-within:ring-2 focus-within:ring-offset-2 active:scale-[0.98]"
                :class="
                    modelValue === card.id
                        ? 'border-cuotas-ink bg-cuotas-ink text-white shadow-[var(--shadow-cuotas-sm)]'
                        : 'hover:border-cuotas-bg-deep hover:-translate-y-0.5'
                "
            >
                <input
                    v-model="modelValue"
                    type="radio"
                    :name="name"
                    :value="card.id"
                    :required="required && index === 0"
                    class="sr-only"
                />
                <span class="flex items-start justify-between gap-3">
                    <span class="min-w-0">
                        <span class="block truncate text-sm font-semibold">
                            {{ card.alias ?? card.bank }}
                        </span>
                        <span
                            class="mt-0.5 block text-xs"
                            :class="
                                modelValue === card.id
                                    ? 'text-white/70'
                                    : 'text-cuotas-muted'
                            "
                        >
                            {{ card.bank }}
                        </span>
                    </span>
                    <span
                        class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full border"
                        :class="
                            modelValue === card.id
                                ? 'text-cuotas-ink border-white bg-white'
                                : 'border-cuotas-border bg-white'
                        "
                        aria-hidden="true"
                    >
                        <span
                            v-if="modelValue === card.id"
                            class="h-2 w-2 rounded-full bg-current"
                        />
                    </span>
                </span>
                <span
                    class="font-[family-name:var(--font-head)] text-base font-semibold tracking-[0.12em]"
                >
                    ···· {{ card.last_four }}
                </span>
            </label>
        </div>
        <InputError :message="error" />
    </div>
</template>

<style scoped>
.card-carousel {
    scrollbar-width: thin;
    scrollbar-color: var(--color-cuotas-border) transparent;
}
</style>
