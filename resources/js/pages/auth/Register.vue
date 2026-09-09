<script setup lang="ts">
import { Form, Head, setLayoutProps } from '@inertiajs/vue3';
import GoogleAuthButton from '@/components/GoogleAuthButton.vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import TextLink from '@/components/TextLink.vue';
import { useTrans } from '@/composables/useTrans';
import { login } from '@/routes';
import { store } from '@/routes/register';

const { t } = useTrans();

setLayoutProps({
    title: t('auth.register.title'),
    description: t('auth.register.description'),
});

defineProps<{
    passwordRules: string;
}>();
</script>

<template>
    <Head :title="t('auth.register.title')" />

    <div>
        <div class="grid gap-2">
            <GoogleAuthButton />
        </div>

        <div class="my-6 flex items-center gap-3">
            <span class="border-cuotas-border h-px flex-1 border-t" />
            <span class="text-cuotas-muted text-xs">{{ t('auth.or_fill') }}</span>
            <span class="border-cuotas-border h-px flex-1 border-t" />
        </div>

        <Form
            v-bind="store.form()"
            :reset-on-success="['password', 'password_confirmation']"
            v-slot="{ errors, processing }"
            class="flex flex-col gap-6"
        >
            <div class="grid gap-4">
                <div class="grid gap-1.5">
                    <label
                        for="name"
                        class="text-cuotas-muted text-xs font-medium"
                        >{{ t('auth.name') }}</label
                    >
                    <input
                        id="name"
                        type="text"
                        name="name"
                        required
                        autofocus
                        autocomplete="name"
                        :placeholder="t('auth.name')"
                        class="border-cuotas-border bg-cuotas-surface-2 w-full rounded-[var(--radius-cuotas-sm)] border-[1.5px] px-3.5 py-3 text-sm"
                    />
                    <InputError :message="errors.name" />
                </div>

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
                        required
                        autocomplete="email"
                        placeholder="tu@correo.com"
                        class="border-cuotas-border bg-cuotas-surface-2 w-full rounded-[var(--radius-cuotas-sm)] border-[1.5px] px-3.5 py-3 text-sm"
                    />
                    <InputError :message="errors.email" />
                </div>

                <div class="grid gap-1.5">
                    <label
                        for="password"
                        class="text-cuotas-muted text-xs font-medium"
                        >{{ t('auth.password') }}</label
                    >
                    <PasswordInput
                        id="password"
                        name="password"
                        required
                        autocomplete="new-password"
                        placeholder="••••••••"
                        :passwordrules="passwordRules"
                        class="border-cuotas-border bg-cuotas-surface-2 w-full rounded-[var(--radius-cuotas-sm)] border-[1.5px] px-3.5 py-3 text-sm"
                    />
                    <InputError :message="errors.password" />
                </div>

                <div class="grid gap-1.5">
                    <label
                        for="password_confirmation"
                        class="text-cuotas-muted text-xs font-medium"
                        >{{ t('auth.confirm_password') }}</label
                    >
                    <PasswordInput
                        id="password_confirmation"
                        name="password_confirmation"
                        required
                        autocomplete="new-password"
                        placeholder="••••••••"
                        :passwordrules="passwordRules"
                        class="border-cuotas-border bg-cuotas-surface-2 w-full rounded-[var(--radius-cuotas-sm)] border-[1.5px] px-3.5 py-3 text-sm"
                    />
                    <InputError :message="errors.password_confirmation" />
                </div>

                <button
                    type="submit"
                    class="bg-cuotas-ink mt-2 w-full rounded-full px-5 py-3 text-sm font-semibold text-white disabled:opacity-60"
                    :disabled="processing"
                    data-test="register-user-button"
                >
                    {{ t('auth.register.title') }}
                </button>
            </div>

            <p class="text-cuotas-muted text-center text-sm">
                {{ t('auth.register.have_account') }}
                <TextLink :href="login()" class="text-cuotas-ink font-semibold"
                    >{{ t('auth.register.sign_in') }}</TextLink
                >
            </p>
        </Form>
    </div>
</template>
