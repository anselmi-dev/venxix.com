<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import {
    ChevronLeft,
    ChevronRight,
    Plus,
} from '@animated-color-icons/lucide-vue';
import AddInstallmentModal, {
    type PurchaseCard,
} from '@/components/cuotas/AddInstallmentModal.vue';
import AddNoteModal from '@/components/cuotas/AddNoteModal.vue';
import NotesList, { type NoteItem } from '@/components/cuotas/NotesList.vue';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import {
    combinedPendingTotal,
    formatMoney,
} from '@/composables/useCurrencyDisplay';
import { useTrans } from '@/composables/useTrans';
import { create as createCard, show as showCard } from '@/routes/cards';
import { create as createImport } from '@/routes/imports';
import { panel, pricing } from '@/routes';

defineOptions({
    layout: {
        active: 'panel',
    },
});

type CardItem = {
    id: number;
    ulid: string;
    display_name: string;
    bank: string;
    last_four: string;
    type_label: string;
    pending_uyu: string;
    pending_usd: string;
    active_installments: number;
    sparkline: {
        height: number;
        label: string;
        amount_uyu: number;
        amount_usd: number;
        count: number;
    }[];
    period_label: string | null;
    closing_day: number | null;
    credit_limit: string | null;
    credit_limit_currency: string | null;
};

type CalendarEvent = {
    id: number;
    day: number;
    type: 'due' | 'closing';
    title: string;
    meta: string;
    amount: string | null;
    currency: string | null;
    status: string;
    status_label: string;
};

const props = defineProps<{
    stats: {
        pending_uyu: string;
        pending_usd: string;
        card_count: number;
        next_due: {
            date: string;
            merchant: string;
            amount: string;
            currency: string;
        } | null;
    };
    cards: CardItem[];
    calendar: {
        year: number;
        month: number;
        markedDays: number[];
        closingDays: number[];
        events: CalendarEvent[];
        selectedDay: number | null;
        statusFilter: string;
    };
    notes: NoteItem[];
    noteMaxLength: number;
    userCards: PurchaseCard[];
    features: {
        calendar: boolean;
        notes: boolean;
        reminders: boolean;
        export: boolean;
        fx_auto: boolean;
        import_csv: boolean;
        can_add_card: boolean;
        family: string;
    };
    showBothCurrencies: boolean;
    preferredCurrency: string;
    exchangeRate: string | null;
}>();

const { t, tList } = useTrans();
const monthNames = tList('panel.months');
const dowLabels = tList('panel.dow');

const showPurchaseModal = ref(false);
const showAddNoteModal = ref(false);
const activeBar = ref<string | null>(null);
const calendarLoading = ref(false);

function barKey(cardId: number, idx: number): string {
    return `${cardId}-${idx}`;
}

function toggleBar(key: string): void {
    activeBar.value = activeBar.value === key ? null : key;
}

function closeBar(key: string): void {
    if (activeBar.value === key) {
        activeBar.value = null;
    }
}

function barTooltip(bar: CardItem['sparkline'][number]): string {
    const cuotasLabel =
        bar.count === 1
            ? t('common.installments_one')
            : t('common.installments_other', { count: bar.count });

    if (bar.count === 0) {
        return t('panel.sparkline_empty', { label: bar.label });
    }

    const amounts = [
        bar.amount_uyu > 0 ? formatMoney(bar.amount_uyu, 'UYU') : null,
        bar.amount_usd > 0 ? formatMoney(bar.amount_usd, 'USD') : null,
    ]
        .filter((amount): amount is string => amount !== null)
        .join(' · ');

    return t('panel.sparkline', {
        label: bar.label,
        cuotas: cuotasLabel,
        amounts,
    });
}

type CalendarCell = {
    day: number | null;
    marked: boolean;
    closingMarked: boolean;
    selected: boolean;
    today: boolean;
};

const calendarDays = computed(() => {
    const year = props.calendar.year;
    const month = props.calendar.month - 1;
    const first = new Date(year, month, 1);
    let startOffset = first.getDay() - 1;
    if (startOffset < 0) {
        startOffset = 6;
    }
    const daysInMonth = new Date(year, month + 1, 0).getDate();
    const cells: CalendarCell[] = [];

    for (let i = 0; i < startOffset; i++) {
        cells.push({
            day: null,
            marked: false,
            closingMarked: false,
            selected: false,
            today: false,
        });
    }

    const today = new Date();
    for (let d = 1; d <= daysInMonth; d++) {
        cells.push({
            day: d,
            marked: props.calendar.markedDays.includes(d),
            closingMarked: props.calendar.closingDays.includes(d),
            selected: props.calendar.selectedDay === d,
            today:
                today.getFullYear() === year &&
                today.getMonth() === month &&
                today.getDate() === d,
        });
    }

    return cells;
});

function isSelectableDay(cell: CalendarCell): boolean {
    return (
        cell.day !== null &&
        (cell.marked || cell.closingMarked || cell.selected)
    );
}

function dayCellClass(cell: CalendarCell): string {
    if (cell.day === null) {
        return 'border-transparent bg-transparent';
    }

    if (cell.selected) {
        return 'border-cuotas-ink bg-cuotas-ink cursor-pointer font-semibold text-white shadow-[var(--shadow-cuotas-sm)] hover:-translate-y-0.5 active:translate-y-0 active:scale-95';
    }

    if (cell.marked || cell.closingMarked) {
        return 'border-cuotas-border hover:border-cuotas-bg-deep hover:bg-cuotas-bg-deep/30 cursor-pointer bg-white hover:-translate-y-0.5 hover:shadow-[var(--shadow-cuotas-sm)] active:translate-y-0 active:scale-95';
    }

    return 'border-cuotas-border/60 text-cuotas-muted/50 cursor-default bg-white/50';
}

const preferredPendingTotal = computed(() =>
    combinedPendingTotal(
        props.stats.pending_uyu,
        props.stats.pending_usd,
        props.preferredCurrency,
        props.exchangeRate,
    ),
);

function navigateCalendar(delta: number): void {
    let month = props.calendar.month + delta;
    let year = props.calendar.year;
    if (month < 1) {
        month = 12;
        year--;
    }
    if (month > 12) {
        month = 1;
        year++;
    }
    router.get(
        panel.url(),
        { year, month, status: props.calendar.statusFilter },
        {
            only: ['calendar'],
            preserveState: true,
            preserveScroll: true,
            replace: true,
            onStart: () => {
                calendarLoading.value = true;
            },
            onFinish: () => {
                calendarLoading.value = false;
            },
        },
    );
}

function selectDay(day: number): void {
    const selected = props.calendar.selectedDay === day ? null : day;
    router.get(
        panel.url(),
        {
            year: props.calendar.year,
            month: props.calendar.month,
            status: props.calendar.statusFilter,
            day: selected,
        },
        {
            only: ['calendar'],
            preserveState: true,
            preserveScroll: true,
            replace: true,
            onStart: () => {
                calendarLoading.value = true;
            },
            onFinish: () => {
                calendarLoading.value = false;
            },
        },
    );
}

function filterStatus(status: string): void {
    router.get(
        panel.url(),
        {
            year: props.calendar.year,
            month: props.calendar.month,
            status,
            day: props.calendar.selectedDay,
        },
        {
            only: ['calendar'],
            preserveState: true,
            preserveScroll: true,
            replace: true,
            onStart: () => {
                calendarLoading.value = true;
            },
            onFinish: () => {
                calendarLoading.value = false;
            },
        },
    );
}

function resetDayFilter(): void {
    router.get(
        panel.url(),
        {
            year: props.calendar.year,
            month: props.calendar.month,
            status: props.calendar.statusFilter,
        },
        {
            only: ['calendar'],
            preserveState: true,
            preserveScroll: true,
            replace: true,
            onStart: () => {
                calendarLoading.value = true;
            },
            onFinish: () => {
                calendarLoading.value = false;
            },
        },
    );
}
</script>

<template>
    <Head :title="t('panel.title')" />

    <p class="text-cuotas-muted mb-2 text-[13px] font-medium">
        {{ t('panel.title') }}
    </p>
    <h1 class="mb-1.5 text-3xl font-semibold">{{ t('panel.heading') }}</h1>
    <p class="text-cuotas-muted mb-7 max-w-2xl text-sm leading-relaxed">
        {{ t('panel.lead') }}
    </p>

    <div v-if="showBothCurrencies" class="mb-9 grid gap-3 sm:grid-cols-3">
        <div
            class="border-cuotas-border bg-cuotas-surface-2 rounded-[var(--radius-cuotas-md)] border p-4"
        >
            <p class="text-cuotas-muted text-xs">
                {{ t('panel.pending_uyu') }}
            </p>
            <p class="font-[family-name:var(--font-head)] text-xl font-bold">
                {{ formatMoney(stats.pending_uyu, 'UYU') }}
            </p>
            <p class="text-cuotas-muted text-xs">
                {{ t('common.cards_count', { count: stats.card_count }) }}
            </p>
        </div>
        <div
            class="border-cuotas-border bg-cuotas-surface-2 rounded-[var(--radius-cuotas-md)] border p-4"
        >
            <p class="text-cuotas-muted text-xs">
                {{ t('panel.pending_usd') }}
            </p>
            <p class="font-[family-name:var(--font-head)] text-xl font-bold">
                {{ formatMoney(stats.pending_usd, 'USD') }}
            </p>
        </div>
        <div
            class="border-cuotas-border bg-cuotas-surface-2 rounded-[var(--radius-cuotas-md)] border p-4"
        >
            <p class="text-cuotas-muted text-xs">{{ t('panel.next_due') }}</p>
            <p class="font-[family-name:var(--font-head)] text-xl font-bold">
                {{ stats.next_due?.date ?? '—' }}
            </p>
            <p v-if="stats.next_due" class="text-cuotas-muted text-xs">
                {{ stats.next_due.merchant }} ·
                {{
                    formatMoney(stats.next_due.amount, stats.next_due.currency)
                }}
            </p>
        </div>
    </div>

    <div v-else class="mb-9 grid gap-3 sm:grid-cols-2">
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
            <p class="font-[family-name:var(--font-head)] text-xl font-bold">
                {{ formatMoney(preferredPendingTotal, preferredCurrency) }}
            </p>
            <p class="text-cuotas-muted text-xs">
                {{ t('common.cards_count', { count: stats.card_count }) }}
            </p>
        </div>
        <div
            class="border-cuotas-border bg-cuotas-surface-2 rounded-[var(--radius-cuotas-md)] border p-4"
        >
            <p class="text-cuotas-muted text-xs">{{ t('panel.next_due') }}</p>
            <p class="font-[family-name:var(--font-head)] text-xl font-bold">
                {{ stats.next_due?.date ?? '—' }}
            </p>
            <p v-if="stats.next_due" class="text-cuotas-muted text-xs">
                {{ stats.next_due.merchant }} ·
                {{
                    formatMoney(stats.next_due.amount, stats.next_due.currency)
                }}
            </p>
        </div>
    </div>

    <div class="mb-3.5 flex items-baseline justify-between">
        <h2 class="text-lg font-semibold">{{ t('panel.cards') }}</h2>
        <div class="flex items-center gap-3">
            <Link
                v-if="features.import_csv"
                :href="createImport()"
                class="text-cuotas-ink text-xs font-semibold"
            >
                {{ t('panel.import_csv') }}
            </Link>
            <span class="text-cuotas-muted text-xs">{{
                t('common.active_count', { count: cards.length })
            }}</span>
        </div>
    </div>

    <TooltipProvider>
        <div class="mb-10 grid gap-3.5 sm:grid-cols-2 lg:grid-cols-3">
            <Link
                v-for="card in cards"
                :key="card.id"
                :href="showCard(card.ulid)"
                class="border-cuotas-border bg-cuotas-surface-2 group flex flex-col gap-3.5 rounded-[var(--radius-cuotas-md)] border p-4 transition hover:-translate-y-0.5 hover:shadow-[var(--shadow-cuotas-sm)]"
            >
                <div class="flex items-start justify-between">
                    <div class="flex items-center gap-2.5">
                        <div
                            class="flex h-[34px] w-[34px] items-center justify-center rounded-xl bg-white text-sm font-semibold"
                        >
                            {{ card.bank.charAt(0) }}
                        </div>
                        <div>
                            <p class="text-sm font-semibold">
                                {{ card.display_name }}
                            </p>
                            <p class="text-cuotas-muted text-xs">
                                {{ card.bank }} · {{ card.type_label }}
                            </p>
                        </div>
                    </div>
                    <span class="text-cuotas-muted">→</span>
                </div>
                <div>
                    <p
                        v-if="showBothCurrencies"
                        class="font-[family-name:var(--font-head)] text-xl font-bold"
                    >
                        {{ formatMoney(card.pending_uyu, 'UYU') }}
                    </p>
                    <p
                        v-else
                        class="font-[family-name:var(--font-head)] text-xl font-bold"
                    >
                        {{
                            formatMoney(
                                preferredCurrency === 'UYU'
                                    ? card.pending_uyu
                                    : card.pending_usd,
                                preferredCurrency,
                            )
                        }}
                    </p>
                    <p class="text-cuotas-muted text-[13px]">
                        <template v-if="showBothCurrencies">
                            {{ formatMoney(card.pending_usd, 'USD') }} ·
                        </template>
                        {{
                            t('common.active_installments', {
                                count: card.active_installments,
                            })
                        }}
                    </p>
                </div>
                <div class="flex h-[26px] items-end gap-0.5">
                    <Tooltip
                        v-for="(bar, idx) in card.sparkline"
                        :key="idx"
                        :open="activeBar === barKey(card.id, idx)"
                    >
                        <TooltipTrigger
                            as="span"
                            role="button"
                            tabindex="0"
                            class="bg-cuotas-border block flex-1 cursor-pointer rounded-sm outline-none"
                            :class="{ 'bg-cuotas-bg-deep': bar.height > 0 }"
                            :style="{ height: `${Math.max(bar.height, 8)}%` }"
                            @click.prevent.stop="
                                toggleBar(barKey(card.id, idx))
                            "
                            @mouseenter="activeBar = barKey(card.id, idx)"
                            @mouseleave="closeBar(barKey(card.id, idx))"
                            @keydown.enter.prevent.stop="
                                toggleBar(barKey(card.id, idx))
                            "
                            @keydown.space.prevent.stop="
                                toggleBar(barKey(card.id, idx))
                            "
                        />
                        <TooltipContent>{{ barTooltip(bar) }}</TooltipContent>
                    </Tooltip>
                </div>
                <div
                    v-if="card.closing_day || card.credit_limit"
                    class="flex flex-wrap gap-1.5 text-[11px]"
                >
                    <span
                        v-if="card.closing_day"
                        class="text-cuotas-red rounded-full bg-[#fbe9e9] px-2 py-0.5 font-semibold"
                    >
                        {{
                            t('panel.closing_badge', { day: card.closing_day })
                        }}
                    </span>
                    <span
                        v-if="card.credit_limit"
                        class="text-cuotas-muted rounded-full bg-white px-2 py-0.5 font-semibold"
                    >
                        {{
                            t('panel.credit_limit_badge', {
                                amount: formatMoney(
                                    card.credit_limit,
                                    card.credit_limit_currency ?? 'UYU',
                                ),
                            })
                        }}
                    </span>
                </div>
                <div
                    class="border-cuotas-border text-cuotas-muted flex justify-between border-t pt-3 text-xs"
                >
                    <span>{{ card.period_label ?? '—' }}</span>
                    <span
                        class="text-cuotas-ink inline-flex items-center gap-1 font-semibold"
                    >
                        <span
                            class="-translate-x-1.5 opacity-0 transition-all duration-200 group-hover:translate-x-0 group-hover:opacity-100"
                            aria-hidden="true"
                            >→</span
                        >
                        {{ t('panel.see_summary') }}
                    </span>
                </div>
            </Link>

            <Link
                :href="features.can_add_card ? createCard.url() : pricing.url()"
                class="al-icon-wrapper border-cuotas-border text-cuotas-muted hover:border-cuotas-bg-deep hover:text-cuotas-ink flex min-h-[168px] flex-col items-center justify-center gap-2 rounded-[var(--radius-cuotas-md)] border-[1.5px] border-dashed transition"
            >
                <span
                    class="flex h-[34px] w-[34px] items-center justify-center rounded-full border border-current"
                >
                    <Plus class="h-4 w-4" />
                </span>
                <span class="text-[13px]">{{
                    features.can_add_card
                        ? t('panel.add_card')
                        : t('panel.upgrade_cards')
                }}</span>
            </Link>
        </div>
    </TooltipProvider>

    <div class="mb-6 grid gap-4 lg:grid-cols-2">
        <div
            :aria-busy="calendarLoading"
            class="border-cuotas-border bg-cuotas-surface-2 relative overflow-hidden rounded-[var(--radius-cuotas-lg)] border p-6"
            :class="{ 'pointer-events-none opacity-70': calendarLoading }"
        >
            <div class="mb-4 flex items-center justify-between">
                <h3 class="text-lg font-bold">
                    {{ monthNames[calendar.month - 1] }}
                    <span class="text-cuotas-muted font-medium">{{
                        calendar.year
                    }}</span>
                </h3>
                <div class="flex gap-1.5">
                    <button
                        type="button"
                        :aria-label="t('panel.prev_month')"
                        :disabled="calendarLoading"
                        class="al-icon-wrapper border-cuotas-border text-cuotas-muted hover:border-cuotas-bg-deep hover:text-cuotas-ink flex h-7 w-7 cursor-pointer items-center justify-center rounded-lg border bg-white transition-colors duration-200 active:scale-95 disabled:cursor-not-allowed disabled:opacity-50"
                        @click="navigateCalendar(-1)"
                    >
                        <ChevronLeft class="h-4 w-4" />
                    </button>
                    <button
                        type="button"
                        :aria-label="t('panel.next_month')"
                        :disabled="calendarLoading"
                        class="al-icon-wrapper border-cuotas-border text-cuotas-muted hover:border-cuotas-bg-deep hover:text-cuotas-ink flex h-7 w-7 cursor-pointer items-center justify-center rounded-lg border bg-white transition-colors duration-200 active:scale-95 disabled:cursor-not-allowed disabled:opacity-50"
                        @click="navigateCalendar(1)"
                    >
                        <ChevronRight class="h-4 w-4" />
                    </button>
                </div>
            </div>
            <div class="grid grid-cols-7 gap-1.5 text-center">
                <div
                    v-for="d in dowLabels"
                    :key="d"
                    class="text-cuotas-muted pb-2 text-[10px] font-bold uppercase"
                >
                    {{ d }}
                </div>
                <button
                    v-for="(cell, idx) in calendarDays"
                    :key="idx"
                    type="button"
                    :disabled="!isSelectableDay(cell) || calendarLoading"
                    :aria-hidden="cell.day === null"
                    :aria-pressed="
                        isSelectableDay(cell) ? cell.selected : undefined
                    "
                    class="focus-visible:ring-cuotas-ink relative flex aspect-square flex-col items-center justify-center gap-0.5 rounded-xl border text-[13px] transition-all duration-200 outline-none focus-visible:ring-2"
                    :class="[
                        dayCellClass(cell),
                        {
                            'ring-cuotas-bg-deep ring-offset-cuotas-surface-2 font-bold ring-2 ring-offset-1':
                                cell.today,
                        },
                    ]"
                    @click="cell.day && selectDay(cell.day)"
                >
                    <span v-if="cell.day">{{ cell.day }}</span>
                    <span
                        v-if="(cell.marked || cell.closingMarked) && cell.day"
                        class="flex items-center gap-0.5"
                    >
                        <span
                            v-if="cell.marked"
                            class="h-1 w-1 rounded-full"
                            :class="
                                cell.selected ? 'bg-white' : 'bg-cuotas-bg-deep'
                            "
                        />
                        <span
                            v-if="cell.closingMarked"
                            class="h-1 w-1 rounded-full"
                            :class="
                                cell.selected ? 'bg-white' : 'bg-cuotas-red'
                            "
                        />
                    </span>
                </button>
            </div>
            <div
                v-if="!features.calendar"
                class="bg-cuotas-surface-2/90 absolute inset-0 flex flex-col items-center justify-center gap-3 px-6 text-center"
            >
                <p class="text-sm font-semibold">
                    {{ t('panel.calendar_pro') }}
                </p>
                <p class="text-cuotas-muted text-xs">
                    {{ t('panel.calendar_pro_hint') }}
                </p>
                <Link
                    :href="pricing()"
                    class="bg-cuotas-ink rounded-full px-4 py-2 text-xs font-semibold text-white"
                    >{{ t('common.see_plans') }}</Link
                >
            </div>
        </div>

        <div
            :aria-busy="calendarLoading"
            class="border-cuotas-border bg-cuotas-surface-2 relative flex flex-col overflow-hidden rounded-[var(--radius-cuotas-lg)] border p-6"
            :class="{ 'pointer-events-none opacity-70': calendarLoading }"
        >
            <div class="mb-3 flex items-center justify-between gap-2">
                <h3 class="text-lg font-bold">{{ t('panel.dues') }}</h3>
                <div class="flex gap-2">
                    <select
                        :disabled="calendarLoading"
                        class="border-cuotas-border hover:border-cuotas-bg-deep cursor-pointer rounded-full border bg-white px-3 py-1.5 text-xs transition-colors duration-200 disabled:cursor-not-allowed disabled:opacity-50"
                        :value="calendar.statusFilter"
                        @change="
                            filterStatus(
                                ($event.target as HTMLSelectElement).value,
                            )
                        "
                    >
                        <option value="all">{{ t('panel.filter_all') }}</option>
                        <option value="pending">
                            {{ t('panel.filter_pending') }}
                        </option>
                        <option value="paid">
                            {{ t('panel.filter_paid') }}
                        </option>
                    </select>
                    <button
                        type="button"
                        class="al-icon-wrapper bg-cuotas-ink inline-flex cursor-pointer items-center gap-1 rounded-full px-3 py-1.5 text-xs font-semibold text-white transition-transform duration-200 hover:scale-[1.03] active:scale-95"
                        @click="showPurchaseModal = true"
                    >
                        <Plus class="h-3.5 w-3.5" />
                        {{ t('panel.add') }}
                    </button>
                </div>
            </div>

            <p
                v-if="calendar.selectedDay"
                class="text-cuotas-muted mb-3 flex items-center gap-2 text-xs"
            >
                {{ t('panel.day_filter') }}
                <button
                    type="button"
                    class="text-cuotas-ink cursor-pointer font-semibold hover:underline"
                    @click="resetDayFilter"
                >
                    {{ t('panel.see_all') }}
                </button>
            </p>

            <div class="flex max-h-[330px] flex-col gap-2.5 overflow-y-auto">
                <div
                    v-for="event in calendar.events"
                    :key="`${event.type}-${event.id}`"
                    class="border-cuotas-border flex gap-3.5 rounded-[var(--radius-cuotas-md)] border bg-white p-3"
                    :class="{
                        'border-l-cuotas-red border-l-[3px]':
                            event.type === 'closing',
                    }"
                >
                    <div class="w-[42px] shrink-0 text-center">
                        <p
                            class="font-[family-name:var(--font-head)] text-xl leading-none font-bold"
                        >
                            {{ event.day }}
                        </p>
                    </div>
                    <div class="min-w-0 flex-1">
                        <span
                            class="mb-1.5 inline-block rounded-full px-2 py-0.5 text-[10px] font-bold"
                            :class="{
                                'text-cuotas-blue bg-[#e6ecff]':
                                    event.status === 'pendiente',
                                'text-cuotas-green bg-[#e7f5ee]':
                                    event.status === 'pagada',
                                'border border-[#dbe98a] bg-[#f2fad0] text-[#6b6913]':
                                    event.status === 'pronto',
                                'text-cuotas-red bg-[#fbe9e9]':
                                    event.status === 'closing',
                            }"
                        >
                            {{ event.status_label }}
                        </span>
                        <p class="text-[13.5px] font-semibold">
                            {{ event.title }}
                        </p>
                        <p class="text-cuotas-muted mt-1 text-[11.5px]">
                            {{ event.meta
                            }}<template v-if="event.amount && event.currency">
                                ·
                                {{ formatMoney(event.amount, event.currency) }}
                            </template>
                        </p>
                    </div>
                </div>
                <p
                    v-if="calendar.events.length === 0"
                    class="text-cuotas-muted py-9 text-center text-[13px]"
                >
                    {{ t('panel.no_dues') }}
                </p>
            </div>
            <div
                v-if="!features.calendar"
                class="bg-cuotas-surface-2/90 absolute inset-0 flex flex-col items-center justify-center gap-3 px-6 text-center"
            >
                <p class="text-sm font-semibold">{{ t('panel.dues_pro') }}</p>
                <Link
                    :href="pricing()"
                    class="bg-cuotas-ink rounded-full px-4 py-2 text-xs font-semibold text-white"
                    >{{ t('common.see_plans') }}</Link
                >
            </div>
        </div>
    </div>

    <AddInstallmentModal
        v-model:open="showPurchaseModal"
        :cards="userCards"
        :title="t('panel.new_purchase')"
    />

    <div
        class="border-cuotas-border bg-cuotas-surface-2 relative mb-6 overflow-hidden rounded-[var(--radius-cuotas-md)] border p-5"
    >
        <div class="mb-4 flex items-center justify-between gap-3">
            <h3 class="text-base font-semibold">{{ t('panel.notes') }}</h3>
            <button
                type="button"
                :aria-label="t('notes.add')"
                class="al-icon-wrapper bg-cuotas-ink flex h-8 w-8 cursor-pointer items-center justify-center rounded-full text-white transition-transform duration-200 hover:scale-[1.05] active:scale-95"
                @click="showAddNoteModal = true"
            >
                <Plus class="h-4 w-4" />
            </button>
        </div>

        <NotesList :notes="notes" />

        <div
            v-if="!features.notes"
            class="bg-cuotas-surface-2/90 absolute inset-0 flex flex-col items-center justify-center gap-3 px-6 text-center"
        >
            <p class="text-sm font-semibold">{{ t('panel.notes_pro') }}</p>
            <Link
                :href="pricing()"
                class="bg-cuotas-ink rounded-full px-4 py-2 text-xs font-semibold text-white"
                >{{ t('common.see_plans') }}</Link
            >
        </div>
    </div>

    <AddNoteModal v-model:open="showAddNoteModal" :max-length="noteMaxLength" />
</template>

<style scoped>
.cuotas-input {
    width: 100%;
    border-radius: var(--radius-cuotas-sm);
    border: 1.5px solid var(--color-cuotas-border);
    background: white;
    padding: 0.625rem 0.75rem;
    font-size: 0.875rem;
}
</style>
