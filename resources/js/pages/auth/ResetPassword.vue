<script setup lang="ts">
import { Form, Head, setLayoutProps } from '@inertiajs/vue3';
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { useTrans } from '@/composables/useTrans';
import { update } from '@/routes/password';

const { t } = useTrans();

setLayoutProps({
    title: t('auth.reset.title'),
    description: t('auth.reset.description'),
});

const props = defineProps<{
    token: string;
    email: string;
    passwordRules: string;
}>();

const inputEmail = ref(props.email);
</script>

<template>
    <Head :title="t('auth.reset.title')" />

    <Form
        v-bind="update.form()"
        :transform="(data) => ({ ...data, token, email })"
        :reset-on-success="['password', 'password_confirmation']"
        v-slot="{ errors, processing }"
    >
        <div class="grid gap-4">
            <div class="grid gap-1.5">
                <label
                    for="email"
                    class="text-xs font-medium text-cuotas-muted"
                    >{{ t('auth.email_short') }}</label
                >
                <input
                    id="email"
                    type="email"
                    name="email"
                    v-model="inputEmail"
                    readonly
                    class="w-full rounded-[var(--radius-cuotas-sm)] border-[1.5px] border-cuotas-border bg-cuotas-surface-2 px-3.5 py-3 text-sm"
                />
                <InputError :message="errors.email" />
            </div>

            <div class="grid gap-1.5">
                <label
                    for="password"
                    class="text-xs font-medium text-cuotas-muted"
                    >{{ t('auth.reset.new_password') }}</label
                >
                <PasswordInput
                    id="password"
                    name="password"
                    autofocus
                    placeholder="••••••••"
                    :passwordrules="passwordRules"
                    class="w-full rounded-[var(--radius-cuotas-sm)] border-[1.5px] border-cuotas-border bg-cuotas-surface-2 px-3.5 py-3 text-sm"
                />
                <InputError :message="errors.password" />
            </div>

            <div class="grid gap-1.5">
                <label
                    for="password_confirmation"
                    class="text-xs font-medium text-cuotas-muted"
                    >{{ t('auth.confirm_password') }}</label
                >
                <PasswordInput
                    id="password_confirmation"
                    name="password_confirmation"
                    placeholder="••••••••"
                    :passwordrules="passwordRules"
                    class="w-full rounded-[var(--radius-cuotas-sm)] border-[1.5px] border-cuotas-border bg-cuotas-surface-2 px-3.5 py-3 text-sm"
                />
                <InputError :message="errors.password_confirmation" />
            </div>

            <button
                type="submit"
                class="mt-2 w-full rounded-full bg-cuotas-ink px-5 py-3 text-sm font-semibold text-white disabled:opacity-60"
                :disabled="processing"
                data-test="reset-password-button"
            >
                {{ t('auth.reset.save') }}
            </button>
        </div>
    </Form>
</template>
