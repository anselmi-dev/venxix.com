<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { useTrans } from '@/composables/useTrans';

defineProps<{
    preferredCurrency: string;
    showBothCurrencies: boolean;
}>();

const { t } = useTrans();

const preferredCurrency = ref('UYU');
const showBoth = ref(true);
</script>

<template>
    <Head :title="t('onboarding.currency_title')" />

    <div
        class="bg-cuotas-bg flex min-h-svh items-center justify-center p-3.5 sm:p-7"
    >
        <div
            class="bg-cuotas-surface w-full max-w-md rounded-[var(--radius-cuotas-xl)] p-8 shadow-[var(--shadow-cuotas)] sm:p-11"
        >
            <div class="mb-6 flex gap-1.5">
                <i class="bg-cuotas-bg-deep block h-1 w-[26px] rounded" />
                <i class="bg-cuotas-bg-deep block h-1 w-[26px] rounded" />
                <i class="bg-cuotas-bg-deep block h-1 w-[26px] rounded" />
            </div>

            <p class="text-cuotas-muted mb-2 text-[13px] font-medium">
                {{ t('onboarding.step_3') }}
            </p>
            <h1 class="mb-2 text-2xl font-semibold">
                {{ t('onboarding.currency_heading') }}
            </h1>
            <p class="text-cuotas-muted mb-6 text-sm">
                {{ t('onboarding.currency_body') }}
            </p>

            <Form
                action="/onboarding/moneda"
                method="post"
                v-slot="{ errors, processing }"
            >
                <div class="mb-5 grid grid-cols-2 gap-3">
                    <label class="relative">
                        <input
                            v-model="preferredCurrency"
                            type="radio"
                            name="preferred_currency"
                            value="UYU"
                            class="peer absolute inset-0 opacity-0"
                        />
                        <div
                            class="border-cuotas-border bg-cuotas-surface-2 peer-checked:border-cuotas-bg-deep rounded-[var(--radius-cuotas-sm)] border-[1.5px] p-4 peer-checked:bg-[#f2fad0]"
                        >
                            <span
                                class="font-[family-name:var(--font-head)] text-xl font-bold"
                                >$</span
                            >
                            <p class="text-[13px]">{{ t('onboarding.uyu') }}</p>
                            <p class="text-cuotas-muted text-[11px]">UYU</p>
                        </div>
                    </label>
                    <label class="relative">
                        <input
                            v-model="preferredCurrency"
                            type="radio"
                            name="preferred_currency"
                            value="USD"
                            class="peer absolute inset-0 opacity-0"
                        />
                        <div
                            class="border-cuotas-border bg-cuotas-surface-2 peer-checked:border-cuotas-bg-deep rounded-[var(--radius-cuotas-sm)] border-[1.5px] p-4 peer-checked:bg-[#f2fad0]"
                        >
                            <span
                                class="font-[family-name:var(--font-head)] text-xl font-bold"
                                >U$</span
                            >
                            <p class="text-[13px]">{{ t('onboarding.usd') }}</p>
                            <p class="text-cuotas-muted text-[11px]">USD</p>
                        </div>
                    </label>
                </div>
                <InputError :message="errors.preferred_currency" />

                <div
                    class="border-cuotas-border mb-6 flex items-center justify-between border-t py-3.5"
                >
                    <div>
                        <p class="text-sm font-medium">
                            {{ t('onboarding.show_both') }}
                        </p>
                        <p class="text-cuotas-muted text-xs">
                            {{ t('onboarding.show_both_hint') }}
                        </p>
                    </div>
                    <input
                        v-model="showBoth"
                        type="checkbox"
                        name="show_both_currencies"
                        value="1"
                        class="h-5 w-5 rounded"
                    />
                </div>

                <button
                    type="submit"
                    class="bg-cuotas-ink w-full rounded-full px-5 py-3 text-sm font-semibold text-white"
                    :disabled="processing"
                >
                    {{ t('onboarding.go_panel') }}
                </button>
            </Form>
        </div>
    </div>
</template>
