<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { useTrans } from '@/composables/useTrans';

defineProps<{
    name: string;
}>();

const { t } = useTrans();
</script>

<template>
    <Head :title="t('onboarding.welcome_title')" />

    <div
        class="bg-cuotas-bg flex min-h-svh items-center justify-center p-3.5 sm:p-7"
    >
        <div
            class="bg-cuotas-surface w-full max-w-md rounded-[var(--radius-cuotas-xl)] p-8 text-center shadow-[var(--shadow-cuotas)] sm:p-11"
        >
            <div class="mb-6 flex justify-center gap-1.5">
                <i class="bg-cuotas-bg-deep block h-1 w-[26px] rounded" />
                <i class="bg-cuotas-border block h-1 w-[26px] rounded" />
                <i class="bg-cuotas-border block h-1 w-[26px] rounded" />
            </div>

            <p class="text-cuotas-muted mb-2 text-[13px] font-medium">
                {{ t('onboarding.welcome_kicker') }}
            </p>
            <h1 class="mb-2.5 text-2xl font-semibold">
                {{
                    t('onboarding.welcome_heading', {
                        name: name.split(' ')[0],
                    })
                }}
            </h1>
            <p class="text-cuotas-muted mb-7 text-sm leading-relaxed">
                {{ t('onboarding.welcome_body') }}
            </p>

            <Link
                href="/onboarding/tarjeta"
                class="bg-cuotas-ink mb-4 inline-flex w-full items-center justify-center rounded-full px-5 py-3 text-sm font-semibold text-white"
            >
                {{ t('onboarding.start') }}
            </Link>

            <Form action="/onboarding/saltear" method="post" class="inline">
                <button
                    type="submit"
                    class="text-cuotas-ink text-[13px] font-semibold"
                >
                    {{ t('onboarding.skip') }}
                </button>
            </Form>
        </div>
    </div>
</template>
