<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import {
    ArrowLeft,
    ArrowRight,
    Download,
    Sparkles,
    Upload,
} from '@animated-color-icons/lucide-vue';
import CardCarouselSelect, {
    type PurchaseCard,
} from '@/components/cuotas/CardCarouselSelect.vue';
import InputError from '@/components/InputError.vue';
import { useTrans } from '@/composables/useTrans';
import { panel } from '@/routes';
import { show, store, template } from '@/routes/imports';

defineOptions({
    layout: {
        active: 'panel',
    },
});

const props = defineProps<{
    cards: PurchaseCard[];
    preselectedCardId: number | null;
    recentImports: {
        ulid: string;
        name: string;
        status: string;
        rows_count: number;
        created_at: string;
    }[];
}>();

const { t } = useTrans();

const selectedCardId = ref<number | null>(
    props.preselectedCardId ?? props.cards[0]?.id ?? null,
);

const statusLabels = computed<Record<string, string>>(() => ({
    pending: t('imports.pending'),
    processing: t('imports.processing'),
    ready: t('imports.ready'),
    failed: t('imports.failed'),
    confirmed: t('imports.confirmed'),
}));
</script>

<template>
    <div>
        <Head :title="t('imports.title')" />

        <Link
            :href="panel()"
            class="al-icon-wrapper text-cuotas-muted hover:text-cuotas-ink mb-5 inline-flex items-center gap-1 text-[13px]"
        >
            <ArrowLeft class="h-3.5 w-3.5" />
            {{ t('summary.back_panel') }}
        </Link>

        <p class="text-cuotas-muted mb-2 text-[13px] font-medium">
            {{ t('imports.kicker') }}
        </p>
        <h1 class="mb-1.5 text-3xl font-semibold">{{ t('imports.title') }}</h1>
        <p class="text-cuotas-muted mb-7 max-w-2xl text-sm leading-relaxed">
            {{ t('imports.lead') }}
        </p>

        <div class="grid gap-6 lg:grid-cols-[1fr_320px]">
            <Form
                v-if="cards.length > 0"
                v-bind="store.form()"
                enctype="multipart/form-data"
                #default="{ errors, processing, progress }"
                class="border-cuotas-border bg-cuotas-surface-2 rounded-[var(--radius-cuotas-lg)] border p-6"
            >
                <CardCarouselSelect
                    v-model="selectedCardId"
                    :cards="cards"
                    :label="t('imports.card')"
                    :error="errors.card_id"
                    class="mb-5"
                />

                <div class="mb-5 grid gap-1.5">
                    <label
                        for="statement"
                        class="text-cuotas-muted text-xs font-medium"
                    >
                        {{ t('imports.csv_file') }}
                    </label>
                    <input
                        id="statement"
                        name="statement"
                        type="file"
                        accept=".csv,.txt,text/csv"
                        required
                        class="cuotas-input file:bg-cuotas-ink file:mr-3 file:rounded-full file:border-0 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-white"
                    />
                    <InputError :message="errors.statement" />
                    <p class="text-cuotas-muted text-xs">
                        {{ t('imports.max_size') }}
                    </p>
                </div>

                <div v-if="progress" class="mb-4">
                    <div
                        class="text-cuotas-muted mb-1 flex justify-between text-xs"
                    >
                        <span>{{ t('imports.uploading') }}</span>
                        <span>{{ progress.percentage }}%</span>
                    </div>
                    <div
                        class="bg-cuotas-border h-1.5 overflow-hidden rounded-full"
                    >
                        <div
                            class="bg-cuotas-bg-deep h-full"
                            :style="{ width: `${progress.percentage}%` }"
                        />
                    </div>
                </div>

                <button
                    type="submit"
                    :disabled="processing"
                    class="al-icon-wrapper bg-cuotas-ink inline-flex items-center gap-1.5 rounded-full px-5 py-2.5 text-sm font-semibold text-white disabled:opacity-60"
                >
                    <Upload class="h-4 w-4" />
                    {{
                        processing
                            ? `${t('imports.uploading')}…`
                            : t('imports.process_file')
                    }}
                </button>
            </Form>

            <div
                v-else
                class="border-cuotas-border bg-cuotas-surface-2 rounded-[var(--radius-cuotas-lg)] border p-6"
            >
                <p class="font-semibold">{{ t('imports.need_card') }}</p>
                <p class="text-cuotas-muted mt-2 text-sm">
                    {{ t('imports.card_required_hint') }}
                </p>
            </div>

            <aside
                class="bg-cuotas-ink rounded-[var(--radius-cuotas-lg)] p-6 text-white"
            >
                <h2 class="text-lg font-semibold">{{ t('imports.format') }}</h2>
                <p class="mt-2 text-xs leading-relaxed text-[#b9b9b9]">
                    {{ t('imports.format_hint') }}
                </p>
                <a
                    :href="template.url()"
                    class="al-icon-wrapper bg-cuotas-bg text-cuotas-ink mt-5 inline-flex items-center gap-1.5 rounded-full px-4 py-2 text-xs font-semibold"
                >
                    <Download class="h-3.5 w-3.5" />
                    {{ t('imports.download_template') }}
                </a>
            </aside>
        </div>

        <div
            class="border-cuotas-border bg-cuotas-surface-2 mt-6 flex items-start gap-3 rounded-[var(--radius-cuotas-lg)] border border-dashed p-5"
        >
            <span
                class="bg-cuotas-ink flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-white"
            >
                <Sparkles class="h-4 w-4" />
            </span>
            <div>
                <p class="text-sm font-semibold">
                    {{ t('imports.ai_coming_soon_title') }}
                </p>
                <p class="text-cuotas-muted mt-1 text-xs leading-relaxed">
                    {{ t('imports.ai_coming_soon_text') }}
                </p>
            </div>
        </div>

        <section v-if="recentImports.length > 0" class="mt-9">
            <h2 class="mb-3 text-lg font-semibold">
                {{ t('imports.recent') }}
            </h2>
            <div
                class="border-cuotas-border overflow-hidden rounded-[var(--radius-cuotas-md)] border"
            >
                <Link
                    v-for="item in recentImports"
                    :key="item.ulid"
                    :href="show({ statementImport: item.ulid })"
                    class="al-icon-wrapper border-cuotas-border flex items-center justify-between gap-4 border-b bg-white px-4 py-3 last:border-b-0"
                >
                    <div>
                        <p class="text-sm font-semibold">{{ item.name }}</p>
                        <p class="text-cuotas-muted text-xs">
                            {{ item.rows_count }} filas · {{ item.created_at }}
                        </p>
                    </div>
                    <span
                        class="text-cuotas-muted inline-flex items-center gap-1 text-xs font-semibold"
                    >
                        {{ statusLabels[item.status] ?? item.status }}
                        <ArrowRight class="h-3.5 w-3.5" />
                    </span>
                </Link>
            </div>
        </section>
    </div>
</template>

<style scoped>
.cuotas-input {
    width: 100%;
    border-radius: var(--radius-cuotas-sm);
    border: 1.5px solid var(--color-cuotas-border);
    background: white;
    padding: 0.75rem 0.875rem;
    font-size: 0.875rem;
}
</style>
