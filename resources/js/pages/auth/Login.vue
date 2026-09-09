<script setup lang="ts">
import { Form, Head, setLayoutProps } from '@inertiajs/vue3';
import GoogleAuthButton from '@/components/GoogleAuthButton.vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import PasskeyVerify from '@/components/PasskeyVerify.vue';
import TextLink from '@/components/TextLink.vue';
import { useTrans } from '@/composables/useTrans';
import { register } from '@/routes';
import { store } from '@/routes/login';
import { request } from '@/routes/password';

const { t } = useTrans();

setLayoutProps({
    title: t('auth.login.title'),
    description: t('auth.login.description'),
});

defineProps<{
    status?: string;
    canResetPassword: boolean;
}>();
</script>

<template>
    <Head :title="t('auth.login.title')" />

    <div>
        <p class="text-cuotas-muted mb-2 text-[13px] font-medium">
            {{ t('auth.login.welcome') }}
        </p>

        <div
            v-if="status"
            class="text-cuotas-green mb-4 text-center text-sm font-medium"
        >
            {{ status }}
        </div>

        <div class="mb-3 grid gap-2">
            <GoogleAuthButton />
        </div>

        <PasskeyVerify />

        <Form
            v-bind="store.form()"
            :reset-on-success="['password']"
            v-slot="{ errors, processing }"
            class="flex flex-col gap-6"
        >
            <div class="grid gap-4">
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
                        autofocus
                        autocomplete="email"
                        placeholder="tu@correo.com"
                        class="border-cuotas-border bg-cuotas-surface-2 w-full rounded-[var(--radius-cuotas-sm)] border-[1.5px] px-3.5 py-3 text-sm"
                    />
                    <InputError :message="errors.email" />
                </div>

                <div class="grid gap-1.5">
                    <div class="flex items-center justify-between">
                        <label
                            for="password"
                            class="text-cuotas-muted text-xs font-medium"
                            >{{ t('auth.password') }}</label
                        >
                        <TextLink
                            v-if="canResetPassword"
                            :href="request()"
                            class="text-cuotas-ink text-xs font-semibold"
                        >
                            {{ t('auth.login.forgot') }}
                        </TextLink>
                    </div>
                    <PasswordInput
                        id="password"
                        name="password"
                        required
                        autocomplete="current-password"
                        placeholder="••••••••"
                        class="border-cuotas-border bg-cuotas-surface-2 w-full rounded-[var(--radius-cuotas-sm)] border-[1.5px] px-3.5 py-3 text-sm"
                    />
                    <InputError :message="errors.password" />
                </div>

                <label class="flex items-center gap-2 text-sm">
                    <input
                        type="checkbox"
                        name="remember"
                        class="border-cuotas-border rounded"
                    />
                    {{ t('auth.remember') }}
                </label>

                <button
                    type="submit"
                    class="bg-cuotas-ink mt-2 w-full rounded-full px-5 py-3 text-sm font-semibold text-white disabled:opacity-60"
                    :disabled="processing"
                    data-test="login-button"
                >
                    {{ t('auth.login.title') }}
                </button>
            </div>

            <p class="text-cuotas-muted text-center text-sm">
                {{ t('auth.login.no_account') }}
                <TextLink
                    :href="register()"
                    class="text-cuotas-ink font-semibold"
                    >{{ t('auth.login.create_one') }}</TextLink
                >
            </p>
        </Form>
    </div>
</template>
