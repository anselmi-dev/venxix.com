<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { ref, useTemplateRef } from 'vue';
import {
    combinedPendingTotal,
    formatMoney,
    secondaryAmountLabel,
} from '@/composables/useCurrencyDisplay';
import { useTrans } from '@/composables/useTrans';
import { update } from '@/routes/installments';

export type ChartSegment = {
    id: number;
    merchant: string;
    number: number;
    installment_count: number;
    amount: number;
};

export type ChartRow = {
    label: string;
    amount: number;
    currency: string;
    segments: ChartSegment[];
};

export type Projection = {
    chart: ChartRow[];
    months: {
        key: string;
        label: string;
        total: number;
        items: {
            id: number;
            merchant: string;
            card_label: string | null;
            currency: string;
            amount: string;
            number: number;
            installment_count: number;
            status: string;
        }[];
    }[];
    stats: {
        pending_uyu: string;
        pending_usd: string;
        next_free_month: string | null;
        active_installments: number;
    };
};

const props = withDefaults(
    defineProps<{
        projection: Projection;
        showBothCurrencies?: boolean;
        preferredCurrency?: string;
        exchangeRate?: string | null;
    }>(),
    {
        showBothCurrencies: true,
        preferredCurrency: 'UYU',
        exchangeRate: null,
    },
);

const { t } = useTrans();

const maxChart = Math.max(
    ...props.projection.chart.map((row) => row.amount),
    1,
);

const preferredPendingTotal = combinedPendingTotal(
    props.projection.stats.pending_uyu,
    props.projection.stats.pending_usd,
    props.preferredCurrency,
    props.exchangeRate,
);

const darkestSegmentLightness = 30;
const lightestSegmentLightness = 70;

/**
 * Shades each installment of a month from darkest (largest amount) to lightest.
 */
function segmentColor(index: number, count: number): string {
    const ratio = count > 1 ? index / (count - 1) : 0;
    const lightness =
        darkestSegmentLightness +
        ratio * (lightestSegmentLightness - darkestSegmentLightness);

    return `hsl(72 82% ${lightness}%)`;
}

function segmentLabel(segment: ChartSegment, currency: string): string {
    return `${segment.merchant} · ${t('projection.installment_of', {
        number: segment.number,
        total: segment.installment_count,
    })} · ${formatMoney(segment.amount, currency)}`;
}

function installmentsLabel(count: number): string {
    return count === 1
        ? t('common.installments_one')
        : t('common.installments_other', { count });
}

function activeLabel(count: number): string {
    return count === 1
        ? t('projection.active_one')
        : t('projection.active_other', { count });
}

const hoveredSegmentKey = ref<string | null>(null);

function segmentKey(rowLabel: string, segmentId: number): string {
    return `${rowLabel}:${segmentId}`;
}

function highlightSegment(rowLabel: string, segmentId: number): void {
    hoveredSegmentKey.value = segmentKey(rowLabel, segmentId);
}

function clearHighlight(): void {
    hoveredSegmentKey.value = null;
}

function toggleHighlight(rowLabel: string, segmentId: number): void {
    const key = segmentKey(rowLabel, segmentId);

    hoveredSegmentKey.value = hoveredSegmentKey.value === key ? null : key;
}

function isHighlighted(rowLabel: string, segmentId: number): boolean {
    return hoveredSegmentKey.value === segmentKey(rowLabel, segmentId);
}

/**
 * Dims the sibling installments of the month being hovered, leaving other months untouched.
 */
function isDimmed(row: ChartRow, segmentId: number): boolean {
    return (
        hoveredSegmentsFor(row).length > 0 &&
        !isHighlighted(row.label, segmentId)
    );
}

/**
 * Returns the hovered installment as a 0 or 1 item list so the template can bind it with v-for.
 *
 * @return {ChartSegment[]}
 */
function hoveredSegmentsFor(row: ChartRow): ChartSegment[] {
    return row.segments.filter((segment) =>
        isHighlighted(row.label, segment.id),
    );
}

/**
 * Horizontal center of a segment as a percentage of the full track, clamped to keep the tooltip inside the card.
 */
function segmentCenterPercent(row: ChartRow, segment: ChartSegment): number {
    const index = row.segments.indexOf(segment);
    const amountBefore = row.segments
        .slice(0, index)
        .reduce((total, previous) => total + previous.amount, 0);
    const center = ((amountBefore + segment.amount / 2) / maxChart) * 100;

    return Math.min(Math.max(center, 10), 90);
}

function segmentShare(row: ChartRow, segment: ChartSegment): string {
    if (row.amount <= 0) {
        return '0%';
    }

    return `${Math.round((segment.amount / row.amount) * 100)}%`;
}

const carouselTrack = useTemplateRef<HTMLDivElement>('carouselTrack');

function scrollCarousel(direction: 1 | -1): void {
    const track = carouselTrack.value;

    if (!track) {
        return;
    }

    const card = track.querySelector<HTMLElement>('[data-month-card]');
    const step = card ? card.offsetWidth + 12 : track.clientWidth * 0.8;

    track.scrollBy({ left: step * direction, behavior: 'smooth' });
}
</script>

<template>
    <div>
        <div v-if="showBothCurrencies" class="mb-8 grid gap-3 sm:grid-cols-3">
            <div
                class="border-cuotas-border bg-cuotas-surface-2 rounded-[var(--radius-cuotas-md)] border p-4"
            >
                <p class="text-cuotas-muted text-xs">
                    {{ t('panel.pending_uyu') }}
                </p>
                <p
                    class="font-[family-name:var(--font-head)] text-xl font-bold"
                >
                    {{ formatMoney(projection.stats.pending_uyu, 'UYU') }}
                </p>
            </div>
            <div
                class="border-cuotas-border bg-cuotas-surface-2 rounded-[var(--radius-cuotas-md)] border p-4"
            >
                <p class="text-cuotas-muted text-xs">
                    {{ t('panel.pending_usd') }}
                </p>
                <p
                    class="font-[family-name:var(--font-head)] text-xl font-bold"
                >
                    {{ formatMoney(projection.stats.pending_usd, 'USD') }}
                </p>
            </div>
            <div
                class="border-cuotas-border bg-cuotas-surface-2 rounded-[var(--radius-cuotas-md)] border p-4"
            >
                <p class="text-cuotas-muted text-xs">
                    {{ t('projection.free_of') }}
                </p>
                <p
                    class="font-[family-name:var(--font-head)] text-xl font-bold"
                >
                    {{ projection.stats.next_free_month ?? '—' }}
                </p>
            </div>
        </div>

        <div v-else class="mb-8 grid gap-3 sm:grid-cols-2">
            <div
                class="border-cuotas-border bg-cuotas-surface-2 rounded-[var(--radius-cuotas-md)] border p-4"
            >
                <p class="text-cuotas-muted text-xs">
                    {{
                        t('panel.pending_total', {
                            currency: preferredCurrency,
                        })
                    }}
                </p>
                <p
                    class="font-[family-name:var(--font-head)] text-xl font-bold"
                >
                    {{ formatMoney(preferredPendingTotal, preferredCurrency) }}
                </p>
            </div>
            <div
                class="border-cuotas-border bg-cuotas-surface-2 rounded-[var(--radius-cuotas-md)] border p-4"
            >
                <p class="text-cuotas-muted text-xs">
                    {{ t('projection.free_of') }}
                </p>
                <p
                    class="font-[family-name:var(--font-head)] text-xl font-bold"
                >
                    {{ projection.stats.next_free_month ?? '—' }}
                </p>
            </div>
        </div>

        <div
            v-if="projection.chart.length"
            class="border-cuotas-border bg-cuotas-surface-2 mb-2 rounded-[var(--radius-cuotas-md)] border p-5"
        >
            <div v-for="row in projection.chart" :key="row.label" class="my-4">
                <div class="mb-1.5 flex items-baseline justify-between gap-3">
                    <span class="text-cuotas-muted text-[13px]">
                        {{ row.label }}
                        <span class="text-[11px]">
                            · {{ installmentsLabel(row.segments.length) }}
                        </span>
                    </span>
                    <span
                        class="font-[family-name:var(--font-head)] text-[13px] font-semibold"
                    >
                        {{ formatMoney(row.amount, row.currency) }}
                    </span>
                </div>

                <div class="relative" @mouseleave="clearHighlight">
                    <div
                        v-for="hovered in hoveredSegmentsFor(row)"
                        :key="hovered.id"
                        class="border-cuotas-border pointer-events-none absolute bottom-full z-10 mb-2 -translate-x-1/2 rounded-[var(--radius-cuotas-sm)] border bg-white px-2.5 py-1.5 whitespace-nowrap shadow-[var(--shadow-cuotas-sm)]"
                        :style="{
                            left: `${segmentCenterPercent(row, hovered)}%`,
                        }"
                        role="tooltip"
                    >
                        <p class="text-[12px] leading-tight font-semibold">
                            {{ hovered.merchant }}
                        </p>
                        <p class="text-cuotas-muted text-[11px]">
                            {{
                                t('projection.installment_of', {
                                    number: hovered.number,
                                    total: hovered.installment_count,
                                })
                            }}
                            · {{ segmentShare(row, hovered) }}
                        </p>
                        <p
                            class="font-[family-name:var(--font-head)] text-[12px] font-semibold"
                        >
                            {{ formatMoney(hovered.amount, row.currency) }}
                        </p>
                    </div>

                    <div
                        class="border-cuotas-border h-3.5 overflow-hidden rounded border bg-white"
                    >
                        <div
                            class="flex h-full"
                            :style="{
                                width: `${(row.amount / maxChart) * 100}%`,
                            }"
                        >
                            <button
                                v-for="(segment, index) in row.segments"
                                :key="segment.id"
                                type="button"
                                class="h-full min-w-[2px] cursor-pointer transition duration-150"
                                :class="[
                                    index < row.segments.length - 1
                                        ? 'border-r border-white'
                                        : '',
                                    isDimmed(row, segment.id)
                                        ? 'opacity-25 grayscale'
                                        : '',
                                ]"
                                :style="{
                                    flex: `${segment.amount} 1 0%`,
                                    backgroundColor: segmentColor(
                                        index,
                                        row.segments.length,
                                    ),
                                }"
                                :aria-label="
                                    segmentLabel(segment, row.currency)
                                "
                                @mouseenter="
                                    highlightSegment(row.label, segment.id)
                                "
                                @focus="highlightSegment(row.label, segment.id)"
                                @blur="clearHighlight"
                                @click="toggleHighlight(row.label, segment.id)"
                            />
                        </div>
                    </div>
                </div>

                <div
                    class="mt-1.5 flex flex-wrap gap-x-3 gap-y-1"
                    @mouseleave="clearHighlight"
                >
                    <span
                        v-for="(segment, index) in row.segments"
                        :key="segment.id"
                        class="text-cuotas-muted flex items-center gap-1.5 text-[11px] transition duration-150"
                        :class="
                            isDimmed(row, segment.id)
                                ? 'opacity-40 grayscale'
                                : ''
                        "
                        @mouseenter="highlightSegment(row.label, segment.id)"
                    >
                        <span
                            class="h-2 w-2 shrink-0 rounded-full"
                            :style="{
                                backgroundColor: segmentColor(
                                    index,
                                    row.segments.length,
                                ),
                            }"
                        />
                        <span class="text-cuotas-ink">
                            {{ segment.merchant }}
                        </span>
                        <span>
                            {{
                                t('projection.installment_of', {
                                    number: segment.number,
                                    total: segment.installment_count,
                                })
                            }}
                        </span>
                        <span class="text-cuotas-ink font-semibold">
                            {{ formatMoney(segment.amount, row.currency) }}
                        </span>
                    </span>
                </div>
            </div>
        </div>

        <div
            v-if="projection.months.length === 0"
            class="border-cuotas-border bg-cuotas-surface-2 mt-8 flex flex-col items-center rounded-[var(--radius-cuotas-md)] border p-10 text-center"
        >
            <svg
                viewBox="0 0 140 140"
                class="mb-5 h-32 w-32"
                aria-hidden="true"
            >
                <path
                    d="M18,10 L22,16 M10,18 L17,21 M14,28 L19,25"
                    fill="none"
                    stroke="var(--color-cuotas-ink)"
                    stroke-width="2.5"
                    stroke-linecap="round"
                />
                <circle
                    cx="98"
                    cy="16"
                    r="1.6"
                    fill="var(--color-cuotas-ink)"
                />
                <circle
                    cx="105"
                    cy="24"
                    r="1.2"
                    fill="var(--color-cuotas-ink)"
                />
                <rect
                    x="112"
                    y="12"
                    width="7"
                    height="7"
                    rx="1.5"
                    fill="var(--color-cuotas-bg-deep)"
                    stroke="var(--color-cuotas-ink)"
                    stroke-width="1.5"
                    transform="rotate(18 115.5 15.5)"
                />
                <path
                    d="M34,116 C40,112 46,122 52,117 C58,112 64,122 70,117 C76,112 82,122 88,117 C91,115 93,116 95,118"
                    fill="none"
                    stroke="var(--color-cuotas-ink)"
                    stroke-width="2.5"
                    stroke-linecap="round"
                />
                <path
                    d="M17,108 L21,112 M13,116 L18,117"
                    fill="none"
                    stroke="var(--color-cuotas-bg-deep)"
                    stroke-width="2.5"
                    stroke-linecap="round"
                />

                <g transform="rotate(-7 68 74)">
                    <rect
                        x="24"
                        y="45"
                        width="90"
                        height="58"
                        rx="12"
                        fill="white"
                        stroke="var(--color-cuotas-ink)"
                        stroke-width="3"
                        stroke-linejoin="round"
                    />
                    <rect
                        x="35"
                        y="57"
                        width="18"
                        height="13"
                        rx="3"
                        fill="var(--color-cuotas-bg-deep)"
                        stroke="var(--color-cuotas-ink)"
                        stroke-width="2"
                    />
                    <line
                        x1="35"
                        y1="82"
                        x2="78"
                        y2="82"
                        stroke="var(--color-cuotas-ink)"
                        stroke-width="2.25"
                        stroke-linecap="round"
                        stroke-dasharray="5 4.5"
                    />
                    <line
                        x1="35"
                        y1="92"
                        x2="64"
                        y2="92"
                        stroke="var(--color-cuotas-ink)"
                        stroke-width="2.25"
                        stroke-linecap="round"
                        stroke-dasharray="5 4.5"
                    />
                </g>

                <path
                    d="M100,74 C100,63 91,54 80,54 C69,54 60,63 60,74 C60,85 69,94 80,94 C91,94 100,85 100,74 Z"
                    fill="none"
                    stroke="var(--color-cuotas-ink)"
                    stroke-width="1.5"
                    stroke-dasharray="2 5"
                    opacity="0.35"
                />

                <circle
                    cx="103"
                    cy="103"
                    r="23"
                    fill="var(--color-cuotas-bg-deep)"
                    stroke="var(--color-cuotas-ink)"
                    stroke-width="3"
                />
                <path
                    d="M92,104 L99,111 L115,94"
                    fill="none"
                    stroke="var(--color-cuotas-ink)"
                    stroke-width="4.5"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                />
            </svg>
            <p class="mb-1 text-sm font-semibold">
                {{ t('projection.empty_title') }}
            </p>
            <p
                class="text-cuotas-muted max-w-[32ch] text-[13px] leading-relaxed"
            >
                {{ t('projection.empty_body') }}
            </p>
        </div>

        <div v-if="projection.months.length" class="mt-8">
            <div class="mb-3 flex items-center justify-between gap-4">
                <h2 class="text-lg font-semibold">
                    {{ t('projection.upcoming') }}
                </h2>
                <div class="flex items-center gap-4">
                    <div class="flex items-center gap-3 text-[11px]">
                        <span class="flex items-center gap-1.5">
                            <span
                                class="bg-cuotas-bg-deep border-cuotas-border h-2.5 w-2.5 rounded-full border"
                            />
                            {{ t('projection.pending_legend') }}
                        </span>
                        <span class="flex items-center gap-1.5">
                            <span
                                class="bg-cuotas-green h-2.5 w-2.5 rounded-full"
                            />
                            {{ t('projection.paid_legend') }}
                        </span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <button
                            type="button"
                            :aria-label="t('projection.prev_months')"
                            class="border-cuotas-border hover:bg-cuotas-surface-2 hover:border-cuotas-bg-deep flex h-7 w-7 cursor-pointer items-center justify-center rounded-full border bg-white text-sm leading-none transition-colors duration-200 active:scale-95"
                            @click="scrollCarousel(-1)"
                        >
                            ‹
                        </button>
                        <button
                            type="button"
                            :aria-label="t('projection.next_months')"
                            class="border-cuotas-border hover:bg-cuotas-surface-2 hover:border-cuotas-bg-deep flex h-7 w-7 cursor-pointer items-center justify-center rounded-full border bg-white text-sm leading-none transition-colors duration-200 active:scale-95"
                            @click="scrollCarousel(1)"
                        >
                            ›
                        </button>
                    </div>
                </div>
            </div>

            <div
                ref="carouselTrack"
                class="cuotas-carousel-track flex snap-x snap-mandatory gap-3 overflow-x-auto scroll-smooth pb-2"
            >
                <div
                    v-for="month in projection.months"
                    :key="month.key"
                    data-month-card
                    class="border-cuotas-border bg-cuotas-surface-2 flex w-[240px] shrink-0 snap-start flex-col rounded-[var(--radius-cuotas-md)] border p-4 sm:w-[260px]"
                >
                    <div class="border-cuotas-border mb-3 border-b pb-2.5">
                        <h3
                            class="font-[family-name:var(--font-head)] text-xl font-bold"
                        >
                            {{ month.label }}
                        </h3>
                        <p class="text-cuotas-muted mt-0.5 text-xs">
                            {{ activeLabel(month.total) }}
                        </p>
                    </div>

                    <div class="flex flex-1 flex-col gap-2">
                        <div
                            v-for="item in month.items"
                            :key="item.id"
                            class="rounded-[var(--radius-cuotas-sm)] border-l-4 bg-white p-2.5"
                            :class="
                                item.status === 'paid'
                                    ? 'border-l-cuotas-green'
                                    : 'border-l-cuotas-bg-deep'
                            "
                        >
                            <p class="text-sm leading-tight font-semibold">
                                {{ item.merchant }}
                            </p>
                            <p class="text-cuotas-muted text-[11px]">
                                {{
                                    t('projection.installment_of', {
                                        number: item.number,
                                        total: item.installment_count,
                                    })
                                }}<template v-if="item.card_label">
                                    · {{ item.card_label }}</template
                                >
                            </p>
                            <div
                                class="mt-1.5 flex items-end justify-between gap-2"
                            >
                                <div>
                                    <span
                                        class="font-[family-name:var(--font-head)] text-sm font-semibold"
                                        >{{
                                            formatMoney(
                                                item.amount,
                                                item.currency,
                                            )
                                        }}</span
                                    >
                                    <p
                                        v-if="
                                            showBothCurrencies &&
                                            secondaryAmountLabel(
                                                item.amount,
                                                item.currency,
                                                preferredCurrency,
                                                exchangeRate,
                                            )
                                        "
                                        class="text-cuotas-muted text-[11px]"
                                    >
                                        {{
                                            secondaryAmountLabel(
                                                item.amount,
                                                item.currency,
                                                preferredCurrency,
                                                exchangeRate,
                                            )
                                        }}
                                    </p>
                                </div>
                                <Form
                                    v-bind="
                                        update.form({ installment: item.id })
                                    "
                                >
                                    <input
                                        type="hidden"
                                        name="status"
                                        :value="
                                            item.status === 'paid'
                                                ? 'pending'
                                                : 'paid'
                                        "
                                    />
                                    <button
                                        type="submit"
                                        class="text-cuotas-ink shrink-0 cursor-pointer text-[11px] transition-opacity duration-200 hover:underline hover:opacity-70"
                                    >
                                        {{
                                            item.status === 'paid'
                                                ? t('projection.mark_pending')
                                                : t('projection.mark_paid')
                                        }}
                                    </button>
                                </Form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.cuotas-carousel-track {
    scrollbar-width: none;
    -ms-overflow-style: none;
}

.cuotas-carousel-track::-webkit-scrollbar {
    display: none;
}
</style>
