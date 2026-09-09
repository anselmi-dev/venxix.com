<script setup lang="ts">
import { Form, Head, Link, usePoll } from '@inertiajs/vue3';
import { watch } from 'vue';
import InputError from '@/components/InputError.vue';
import { useTrans } from '@/composables/useTrans';
import { panel } from '@/routes';
import { confirm, create } from '@/routes/imports';

defineOptions({
    layout: {
        active: 'panel',
    },
});

const props = defineProps<{
    statementImport: {
        ulid: string;
        name: string;
        status: 'pending' | 'processing' | 'ready' | 'failed' | 'confirmed';
        error: string | null;
        rows_count: number;
        card: string;
        rows: {
            id: number;
            row_number: number;
            merchant: string | null;
            total_amount: string | null;
            installment_count: number | null;
            currency: string | null;
            first_due_date: string | null;
            validation_errors: Record<string, string[]> | null;
            selected: boolean;
            imported: boolean;
        }[];
    };
}>();

const { t } = useTrans();

const { start, stop } = usePoll(
    2000,
    { only: ['statementImport'] },
    { autoStart: false },
);

watch(
    () => props.statementImport.status,
    (status) => {
        if (status === 'pending' || status === 'processing') {
            start();
        } else {
            stop();
        }
    },
    { immediate: true },
);

function rowError(errors: Record<string, string[]> | null): string {
    if (!errors) {
        return '';
    }

    return Object.values(errors).flat().join(' ');
}
</script>

<template>
    <div>
        <Head :title="t('imports.review_title')" />

        <Link
            :href="create()"
            class="text-cuotas-muted hover:text-cuotas-ink mb-5 inline-flex text-[13px]"
        >
            {{ t('imports.back_imports') }}
        </Link>

        <div class="mb-7 flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-cuotas-muted mb-2 text-[13px] font-medium">
                    {{ statementImport.card }}
                </p>
                <h1 class="text-3xl font-semibold">
                    {{ t('imports.review_title') }}
                </h1>
                <p class="text-cuotas-muted mt-1 text-sm">
                    {{ statementImport.name }}
                </p>
            </div>
            <Link
                :href="panel()"
                class="border-cuotas-border rounded-full border px-4 py-2 text-xs font-semibold"
            >
                {{ t('nav.go_panel') }}
            </Link>
        </div>

        <div
            v-if="
                statementImport.status === 'pending' ||
                statementImport.status === 'processing'
            "
            class="border-cuotas-border bg-cuotas-surface-2 rounded-[var(--radius-cuotas-lg)] border px-6 py-14 text-center"
        >
            <span
                class="border-cuotas-border border-t-cuotas-ink mx-auto mb-4 block h-8 w-8 animate-spin rounded-full border-2"
            />
            <p class="font-semibold">{{ t('imports.reading') }}</p>
            <p class="text-cuotas-muted mt-1 text-sm">
                {{ t('imports.auto_refresh') }}
            </p>
        </div>

        <div
            v-else-if="statementImport.status === 'failed'"
            class="border-cuotas-red rounded-[var(--radius-cuotas-lg)] border bg-[#fff0f0] p-6"
        >
            <p class="text-cuotas-red font-semibold">
                {{ t('imports.process_failed') }}
            </p>
            <p class="text-cuotas-muted mt-2 text-sm">
                {{ statementImport.error }}
            </p>
            <Link
                :href="create()"
                class="bg-cuotas-ink mt-4 inline-flex rounded-full px-4 py-2 text-xs font-semibold text-white"
            >
                {{ t('imports.try_another') }}
            </Link>
        </div>

        <div
            v-else-if="statementImport.status === 'confirmed'"
            class="border-cuotas-green rounded-[var(--radius-cuotas-lg)] border bg-[#e7f5ee] p-6"
        >
            <p class="text-cuotas-green font-semibold">
                {{ t('imports.confirmed') }}
            </p>
            <p class="text-cuotas-muted mt-2 text-sm">
                {{ t('imports.confirmed_hint') }}
            </p>
        </div>

        <Form
            v-else
            v-bind="confirm.form({ statementImport: statementImport.ulid })"
            #default="{ errors, processing }"
        >
            <div
                class="mb-4 rounded-[var(--radius-cuotas-md)] border border-[#dbe98a] bg-[#f2fad0] px-4 py-3 text-sm"
            >
                {{ t('imports.review_hint') }}
            </div>

            <InputError :message="errors.row_ids" class="mb-3" />

            <div
                class="border-cuotas-border overflow-x-auto rounded-[var(--radius-cuotas-lg)] border"
            >
                <table
                    class="w-full min-w-[820px] border-collapse text-left text-sm"
                >
                    <thead
                        class="bg-cuotas-surface-2 text-cuotas-muted text-xs"
                    >
                        <tr>
                            <th class="w-12 px-4 py-3">
                                {{ t('imports.use') }}
                            </th>
                            <th class="px-4 py-3">
                                {{ t('imports.merchant') }}
                            </th>
                            <th class="px-4 py-3">{{ t('imports.amount') }}</th>
                            <th class="px-4 py-3">
                                {{ t('imports.installments') }}
                            </th>
                            <th class="px-4 py-3">
                                {{ t('imports.currency') }}
                            </th>
                            <th class="px-4 py-3">
                                {{ t('imports.first_due') }}
                            </th>
                            <th class="px-4 py-3">{{ t('imports.status') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="row in statementImport.rows"
                            :key="row.id"
                            class="border-cuotas-border border-t bg-white"
                            :class="{ 'bg-[#fff0f0]': row.validation_errors }"
                        >
                            <td class="px-4 py-3">
                                <input
                                    type="checkbox"
                                    name="row_ids[]"
                                    :value="row.id"
                                    :checked="row.selected"
                                    :disabled="Boolean(row.validation_errors)"
                                    class="accent-cuotas-ink h-4 w-4"
                                />
                            </td>
                            <td class="px-4 py-3 font-medium">
                                {{ row.merchant || '—' }}
                            </td>
                            <td class="px-4 py-3">
                                {{ row.total_amount || '—' }}
                            </td>
                            <td class="px-4 py-3">
                                {{ row.installment_count || '—' }}
                            </td>
                            <td class="px-4 py-3">{{ row.currency || '—' }}</td>
                            <td class="px-4 py-3">
                                {{ row.first_due_date || '—' }}
                            </td>
                            <td class="max-w-[260px] px-4 py-3 text-xs">
                                <span
                                    v-if="!row.validation_errors"
                                    class="text-cuotas-green font-semibold"
                                >
                                    {{ t('imports.ready') }}
                                </span>
                                <span v-else class="text-cuotas-red">
                                    {{ rowError(row.validation_errors) }}
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="mt-5 flex justify-end">
                <button
                    type="submit"
                    :disabled="processing"
                    class="bg-cuotas-ink rounded-full px-5 py-2.5 text-sm font-semibold text-white disabled:opacity-60"
                >
                    {{
                        processing
                            ? t('imports.importing')
                            : t('imports.confirm_selected')
                    }}
                </button>
            </div>
        </Form>
    </div>
</template>
