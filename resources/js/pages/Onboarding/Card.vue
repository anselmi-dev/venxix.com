<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import InputError from '@/components/InputError.vue';
import { useTrans } from '@/composables/useTrans';

const { t } = useTrans();

const types = computed(() => [
    { value: 'credit', label: t('cards.type_credit') },
    { value: 'debit', label: t('cards.type_debit') },
    { value: 'account', label: t('cards.type_account') },
]);
</script>

<template>
    <Head :title="t('onboarding.card_title')" />

    <div
        class="bg-cuotas-bg flex min-h-svh items-center justify-center p-3.5 sm:p-7"
    >
        <div
            class="bg-cuotas-surface w-full max-w-md rounded-[var(--radius-cuotas-xl)] p-8 shadow-[var(--shadow-cuotas)] sm:p-11"
        >
            <div class="mb-6 flex gap-1.5">
                <i class="bg-cuotas-bg-deep block h-1 w-[26px] rounded" />
                <i class="bg-cuotas-bg-deep block h-1 w-[26px] rounded" />
                <i class="bg-cuotas-border block h-1 w-[26px] rounded" />
            </div>

            <p class="text-cuotas-muted mb-2 text-[13px] font-medium">
                {{ t('onboarding.step_2') }}
            </p>
            <h1 class="mb-2 text-2xl font-semibold">
                {{ t('onboarding.card_heading') }}
            </h1>
            <p class="text-cuotas-muted mb-6 text-sm leading-relaxed">
                {{ t('onboarding.card_body') }}
            </p>

            <Form
                action="/onboarding/tarjeta"
                method="post"
                v-slot="{ errors, processing }"
                class="text-left"
            >
                <div class="mb-4 grid gap-1.5">
                    <label
                        for="bank"
                        class="text-cuotas-muted text-xs font-medium"
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

                <div class="mb-4 grid gap-4 sm:grid-cols-2">
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
                            placeholder="1796"
                            class="cuotas-input"
                        />
                        <InputError :message="errors.last_four" />
                    </div>
                </div>

                <p class="text-cuotas-muted mb-2 text-xs font-medium">
                    {{ t('cards.type') }}
                </p>
                <div class="mb-6 grid grid-cols-3 gap-2">
                    <label
                        v-for="type in types"
                        :key="type.value"
                        class="relative"
                    >
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

                <button
                    type="submit"
                    class="cuotas-pill block w-full"
                    :disabled="processing"
                >
                    {{ t('common.continue') }}
                </button>
            </Form>

            <Form
                action="/onboarding/tarjeta/saltear"
                method="post"
                class="mt-5 text-center"
            >
                <button
                    type="submit"
                    class="text-cuotas-ink text-[13px] font-semibold"
                >
                    {{ t('onboarding.skip_step') }}
                </button>
            </Form>
        </div>
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
.cuotas-pill {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 999px;
    background: var(--color-cuotas-ink);
    color: white;
    padding: 0.8125rem 1.375rem;
    font-size: 0.875rem;
    font-weight: 600;
}
</style>
