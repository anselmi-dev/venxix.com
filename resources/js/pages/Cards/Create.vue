<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import InputError from '@/components/InputError.vue';
import { useTrans } from '@/composables/useTrans';

defineOptions({
    layout: {
        active: 'panel',
    },
});

const { t } = useTrans();

const types = computed(() => [
    { value: 'credit', label: t('cards.type_credit') },
    { value: 'debit', label: t('cards.type_debit') },
    { value: 'account', label: t('cards.type_account') },
]);

const currencies = ['UYU', 'USD'];
</script>

<template>
    <Head :title="t('card_form.create_title')" />

    <div class="mx-auto max-w-lg">
        <h1 class="mb-6 text-2xl font-semibold">
            {{ t('card_form.create_heading') }}
        </h1>

        <Form
            action="/tarjetas"
            method="post"
            v-slot="{ errors, processing }"
            class="grid gap-4"
        >
            <div class="grid gap-1.5">
                <label for="bank" class="text-cuotas-muted text-xs font-medium"
                    >{{ t('cards.bank') }}</label
                >
                <input
                    id="bank"
                    name="bank"
                    required
                    :placeholder="t('cards.bank_placeholder')"
                    class="cuotas-input"
                />
                <InputError :message="errors.bank" />
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="grid gap-1.5">
                    <label
                        for="alias"
                        class="text-cuotas-muted text-xs font-medium"
                        >{{ t('cards.alias') }}</label
                    >
                    <input
                        id="alias"
                        name="alias"
                        :placeholder="t('cards.alias_placeholder')"
                        class="cuotas-input"
                    />
                    <InputError :message="errors.alias" />
                </div>
                <div class="grid gap-1.5">
                    <label
                        for="last_four"
                        class="text-cuotas-muted text-xs font-medium"
                        >{{ t('cards.last_four') }}</label
                    >
                    <input
                        id="last_four"
                        name="last_four"
                        maxlength="4"
                        required
                        class="cuotas-input"
                    />
                    <InputError :message="errors.last_four" />
                </div>
            </div>
            <div class="grid grid-cols-3 gap-2">
                <label v-for="type in types" :key="type.value" class="relative">
                    <input
                        type="radio"
                        name="type"
                        :value="type.value"
                        :checked="type.value === 'credit'"
                        class="peer absolute inset-0 opacity-0"
                    />
                    <div
                        class="border-cuotas-border bg-cuotas-surface-2 peer-checked:border-cuotas-bg-deep rounded-[var(--radius-cuotas-sm)] border-[1.5px] p-3 text-center text-[13px] peer-checked:bg-[#f2fad0] peer-checked:font-semibold"
                    >
                        {{ type.label }}
                    </div>
                </label>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="grid gap-1.5">
                    <label
                        for="closing_day"
                        class="text-cuotas-muted text-xs font-medium"
                        >{{ t('cards.closing_day') }}</label
                    >
                    <input
                        id="closing_day"
                        name="closing_day"
                        type="number"
                        min="1"
                        max="31"
                        :placeholder="t('cards.closing_day_placeholder')"
                        class="cuotas-input"
                    />
                    <p class="text-cuotas-muted text-xs">
                        {{ t('cards.closing_day_hint') }}
                    </p>
                    <InputError :message="errors.closing_day" />
                </div>
                <div class="grid gap-1.5">
                    <label
                        for="credit_limit"
                        class="text-cuotas-muted text-xs font-medium"
                        >{{ t('cards.credit_limit') }}</label
                    >
                    <div class="flex gap-2">
                        <input
                            id="credit_limit"
                            name="credit_limit"
                            type="number"
                            min="0"
                            step="0.01"
                            :placeholder="t('cards.credit_limit_placeholder')"
                            class="cuotas-input"
                        />
                        <select
                            name="credit_limit_currency"
                            :aria-label="t('cards.credit_limit_currency')"
                            class="cuotas-input w-24"
                        >
                            <option
                                v-for="currency in currencies"
                                :key="currency"
                                :value="currency"
                            >
                                {{ currency }}
                            </option>
                        </select>
                    </div>
                    <InputError :message="errors.credit_limit" />
                    <InputError :message="errors.credit_limit_currency" />
                </div>
            </div>
            <button
                type="submit"
                class="bg-cuotas-ink rounded-full px-5 py-3 text-sm font-semibold text-white"
                :disabled="processing"
            >
                {{ t('common.save') }}
            </button>
            <Link href="/panel" class="text-cuotas-muted text-center text-sm"
                >{{ t('common.cancel') }}</Link
            >
        </Form>
    </div>
</template>

<style scoped>
.cuotas-input {
    width: 100%;
    border-radius: var(--radius-cuotas-sm);
    border: 1.5px solid var(--color-cuotas-border);
    background: var(--color-cuotas-surface-2);
    padding: 0.75rem 0.875rem;
    font-size: 0.875rem;
}
</style>
