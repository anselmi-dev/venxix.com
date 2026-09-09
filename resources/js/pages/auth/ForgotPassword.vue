<script setup lang="ts">
import { Form, Head, setLayoutProps } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { useTrans } from '@/composables/useTrans';
import { login } from '@/routes';
import { email } from '@/routes/password';

const { t } = useTrans();

setLayoutProps({
    title: t('auth.forgot.title'),
    description: t('auth.forgot.description'),
});

defineProps<{
    status?: string;
}>();
</script>

<template>
    <Head :title="t('auth.forgot.title')" />

    <div>
        <div
            v-if="status"
            class="text-cuotas-green mb-4 text-center text-sm font-medium"
        >
            {{ status }}
        </div>

        <Form
            v-bind="email.form()"
            v-slot="{ errors, processing }"
            class="space-y-6"
        >
            <div class="grid gap-1.5">
                <label
                    for="email"
                    class="text-cuotas-muted text-xs font-medium"
                    >{{ t('auth.email') }}</label
                >
                <input
                    id="email"
                    type="email"
                    name="email"
                    autofocus
                    placeholder="tu@correo.com"
                    class="border-cuotas-border bg-cuotas-surface-2 w-full rounded-[var(--radius-cuotas-sm)] border-[1.5px] px-3.5 py-3 text-sm"
                />
                <InputError :message="errors.email" />
            </div>

            <button
                type="submit"
                class="bg-cuotas-ink w-full rounded-full px-5 py-3 text-sm font-semibold text-white disabled:opacity-60"
                :disabled="processing"
                data-test="email-password-reset-link-button"
            >
                {{ t('auth.forgot.send') }}
            </button>
        </Form>

        <p class="text-cuotas-muted mt-6 text-center text-sm">
            <TextLink :href="login()" class="text-cuotas-ink font-semibold"
                >{{ t('auth.forgot.back') }}</TextLink
            >
        </p>
    </div>
</template>
