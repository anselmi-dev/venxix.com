<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import {
    Bell,
    CircleDollarSign,
    CreditCard,
    Eye,
    EyeOff,
    Languages,
    LockKeyhole,
    LogOut,
    Trash2,
    User,
} from '@animated-color-icons/lucide-vue';
import { onMounted, ref } from 'vue';
import InputError from '@/components/InputError.vue';
import LocaleSwitcher from '@/components/cuotas/LocaleSwitcher.vue';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Switch } from '@/components/ui/switch';
import { usePushNotifications } from '@/composables/usePushNotifications';
import { useTrans } from '@/composables/useTrans';
import { billing, pricing } from '@/routes';
import { destroy as destroyAccount } from '@/routes/perfil';

defineOptions({
    layout: {
        active: 'profile',
    },
});

const props = defineProps<{
    user: {
        name: string;
        email: string;
        locale: string;
        preferred_currency: string;
        show_both_currencies: boolean;
        reminders_enabled: boolean;
        usd_uyu_rate: string | null;
        exchange_rate_automatic: boolean;
        exchange_rate_date: string | null;
        exchange_rate_source: string | null;
    };
    plan: {
        name: string;
        family: string;
        price: string;
        is_free: boolean;
    };
    mustVerifyEmail: boolean;
    canManageTwoFactor: boolean;
    twoFactorEnabled: boolean;
    features: {
        reminders: boolean;
    };
    push: {
        enabled: boolean;
        public_key: string | null;
    };
}>();

const { t } = useTrans();

const preferredCurrency = ref(props.user.preferred_currency);
const showBoth = ref(props.user.show_both_currencies);
const remindersEnabled = ref(props.user.reminders_enabled);

const showCurrentPassword = ref(false);
const showNewPassword = ref(false);
const showConfirmPassword = ref(false);

const {
    isSupported: pushSupported,
    isSubscribed: pushSubscribed,
    isLoading: pushLoading,
    error: pushError,
    refreshSubscriptionState,
    subscribe: subscribePush,
    unsubscribe: unsubscribePush,
} = usePushNotifications();

onMounted(() => {
    void refreshSubscriptionState();
});
</script>

<template>
    <Head :title="t('profile.title')" />

    <div class="mb-9 flex items-center gap-4">
        <div
            class="bg-cuotas-surface-2 flex h-16 w-16 items-center justify-center rounded-2xl text-2xl font-semibold"
        >
            {{ user.name.charAt(0) }}
        </div>
        <div>
            <p class="text-cuotas-muted text-[13px] font-medium">
                {{ t('profile.account') }}
            </p>
            <h1 class="text-2xl font-semibold">{{ user.name }}</h1>
            <p class="text-cuotas-muted text-[13px]">{{ user.email }}</p>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-[200px_1fr]">
        <nav class="flex flex-col gap-1 text-sm lg:sticky lg:top-7">
            <a
                href="#datos"
                class="al-icon-wrapper bg-cuotas-surface-2 inline-flex items-center gap-2 rounded-lg px-3 py-2 font-semibold"
            >
                <User class="h-4 w-4 shrink-0" />
                {{ t('profile.personal') }}
            </a>
            <a
                href="#password"
                class="al-icon-wrapper text-cuotas-muted hover:bg-cuotas-surface-2 inline-flex items-center gap-2 rounded-lg px-3 py-2"
            >
                <LockKeyhole class="h-4 w-4 shrink-0" />
                {{ t('profile.password') }}
            </a>
            <a
                href="#idioma"
                class="al-icon-wrapper text-cuotas-muted hover:bg-cuotas-surface-2 inline-flex items-center gap-2 rounded-lg px-3 py-2"
            >
                <Languages class="h-4 w-4 shrink-0" />
                {{ t('profile.language') }}
            </a>
            <a
                href="#moneda"
                class="al-icon-wrapper text-cuotas-muted hover:bg-cuotas-surface-2 inline-flex items-center gap-2 rounded-lg px-3 py-2"
            >
                <CircleDollarSign class="h-4 w-4 shrink-0" />
                {{ t('profile.currency') }}
            </a>
            <a
                v-if="features.reminders"
                href="#notificaciones"
                class="al-icon-wrapper text-cuotas-muted hover:bg-cuotas-surface-2 inline-flex items-center gap-2 rounded-lg px-3 py-2"
            >
                <Bell class="h-4 w-4 shrink-0" />
                {{ t('profile.notifications') }}
            </a>
            <a
                href="#suscripcion"
                class="al-icon-wrapper text-cuotas-muted hover:bg-cuotas-surface-2 inline-flex items-center gap-2 rounded-lg px-3 py-2"
            >
                <CreditCard class="h-4 w-4 shrink-0" />
                {{ t('profile.subscription') }}
            </a>
            <a
                href="#cuenta"
                class="al-icon-wrapper text-cuotas-muted hover:bg-cuotas-surface-2 inline-flex items-center gap-2 rounded-lg px-3 py-2"
            >
                <Trash2 class="h-4 w-4 shrink-0" />
                {{ t('profile.danger_zone') }}
            </a>
            <Link
                href="/logout"
                method="post"
                as="button"
                class="al-icon-wrapper text-cuotas-red mt-3 inline-flex items-center gap-2 rounded-lg px-3 py-2 text-left"
            >
                <LogOut class="h-4 w-4 shrink-0" />
                {{ t('profile.logout') }}
            </Link>
        </nav>

        <div class="flex flex-col gap-4">
            <section
                id="datos"
                class="border-cuotas-border bg-cuotas-surface-2 scroll-mt-6 rounded-[var(--radius-cuotas-md)] border p-6"
            >
                <h2 class="mb-1 text-lg font-semibold">
                    {{ t('profile.personal') }}
                </h2>
                <p class="text-cuotas-muted mb-5 text-[13px]">
                    {{ t('profile.personal_hint') }}
                </p>
                <Form
                    action="/perfil"
                    method="patch"
                    v-slot="{ errors, processing }"
                    class="grid gap-4 sm:grid-cols-2"
                >
                    <div class="grid gap-1.5 sm:col-span-2">
                        <label class="text-cuotas-muted text-xs">{{
                            t('profile.name')
                        }}</label>
                        <input
                            name="name"
                            :value="user.name"
                            required
                            class="cuotas-input"
                        />
                        <InputError :message="errors.name" />
                    </div>
                    <div class="grid gap-1.5 sm:col-span-2">
                        <label class="text-cuotas-muted text-xs">{{
                            t('profile.email')
                        }}</label>
                        <input
                            :value="user.email"
                            type="email"
                            disabled
                            class="cuotas-input disabled:bg-cuotas-border/40 disabled:cursor-not-allowed"
                        />
                        <p class="text-cuotas-muted text-xs">
                            {{ t('profile.email_locked') }}
                        </p>
                    </div>
                    <button
                        type="submit"
                        class="bg-cuotas-ink w-full cursor-pointer rounded-full px-4 py-2 text-sm font-semibold text-white disabled:cursor-not-allowed disabled:opacity-60 sm:col-span-2 sm:w-auto sm:justify-self-end"
                        :disabled="processing"
                    >
                        {{ t('profile.save_data') }}
                    </button>
                </Form>
            </section>

            <section
                id="password"
                class="border-cuotas-border bg-cuotas-surface-2 scroll-mt-6 rounded-[var(--radius-cuotas-md)] border p-6"
            >
                <h2 class="mb-1 text-lg font-semibold">
                    {{ t('profile.password') }}
                </h2>
                <p class="text-cuotas-muted mb-5 text-[13px]">
                    {{ t('profile.password_hint') }}
                </p>
                <Form
                    action="/perfil/contrasena"
                    method="put"
                    v-slot="{ errors, processing }"
                    class="grid max-w-md gap-4"
                >
                    <div class="grid gap-1.5">
                        <label class="text-cuotas-muted text-xs">{{
                            t('profile.current_password')
                        }}</label>
                        <div class="relative">
                            <input
                                name="current_password"
                                :type="
                                    showCurrentPassword ? 'text' : 'password'
                                "
                                required
                                class="cuotas-input pr-10"
                            />
                            <button
                                type="button"
                                tabindex="-1"
                                class="text-cuotas-muted hover:text-cuotas-ink absolute inset-y-0 right-0 flex cursor-pointer items-center px-3"
                                :aria-label="
                                    showCurrentPassword
                                        ? t('profile.hide_password')
                                        : t('profile.show_password')
                                "
                                @click="
                                    showCurrentPassword = !showCurrentPassword
                                "
                            >
                                <EyeOff
                                    v-if="showCurrentPassword"
                                    class="size-4"
                                />
                                <Eye v-else class="size-4" />
                            </button>
                        </div>
                        <InputError :message="errors.current_password" />
                    </div>
                    <div class="grid gap-1.5">
                        <label class="text-cuotas-muted text-xs">{{
                            t('profile.new_password')
                        }}</label>
                        <div class="relative">
                            <input
                                name="password"
                                :type="showNewPassword ? 'text' : 'password'"
                                required
                                class="cuotas-input pr-10"
                            />
                            <button
                                type="button"
                                tabindex="-1"
                                class="text-cuotas-muted hover:text-cuotas-ink absolute inset-y-0 right-0 flex cursor-pointer items-center px-3"
                                :aria-label="
                                    showNewPassword
                                        ? t('profile.hide_password')
                                        : t('profile.show_password')
                                "
                                @click="showNewPassword = !showNewPassword"
                            >
                                <EyeOff v-if="showNewPassword" class="size-4" />
                                <Eye v-else class="size-4" />
                            </button>
                        </div>
                        <InputError :message="errors.password" />
                    </div>
                    <div class="grid gap-1.5">
                        <label class="text-cuotas-muted text-xs">{{
                            t('profile.confirm_password')
                        }}</label>
                        <div class="relative">
                            <input
                                name="password_confirmation"
                                :type="
                                    showConfirmPassword ? 'text' : 'password'
                                "
                                required
                                class="cuotas-input pr-10"
                            />
                            <button
                                type="button"
                                tabindex="-1"
                                class="text-cuotas-muted hover:text-cuotas-ink absolute inset-y-0 right-0 flex cursor-pointer items-center px-3"
                                :aria-label="
                                    showConfirmPassword
                                        ? t('profile.hide_password')
                                        : t('profile.show_password')
                                "
                                @click="
                                    showConfirmPassword = !showConfirmPassword
                                "
                            >
                                <EyeOff
                                    v-if="showConfirmPassword"
                                    class="size-4"
                                />
                                <Eye v-else class="size-4" />
                            </button>
                        </div>
                    </div>
                    <button
                        type="submit"
                        class="bg-cuotas-ink w-full cursor-pointer rounded-full px-4 py-2 text-sm font-semibold text-white disabled:cursor-not-allowed disabled:opacity-60 sm:w-auto sm:justify-self-end"
                        :disabled="processing"
                    >
                        {{ t('profile.update_password') }}
                    </button>
                </Form>
                <p
                    v-if="canManageTwoFactor"
                    class="text-cuotas-muted mt-4 text-sm"
                >
                    2FA:
                    {{
                        twoFactorEnabled
                            ? t('profile.2fa_on')
                            : t('profile.2fa_off')
                    }}
                    —
                    <Link
                        href="/settings/security"
                        class="text-cuotas-ink font-semibold"
                        >{{ t('profile.manage_security') }}</Link
                    >
                </p>
            </section>

            <section
                id="idioma"
                class="border-cuotas-border bg-cuotas-surface-2 scroll-mt-6 rounded-[var(--radius-cuotas-md)] border p-6"
            >
                <h2 class="mb-1 text-lg font-semibold">
                    {{ t('profile.language') }}
                </h2>
                <p class="text-cuotas-muted mb-5 text-[13px]">
                    {{ t('locale.hint') }}
                </p>
                <LocaleSwitcher />
            </section>

            <section
                id="moneda"
                class="border-cuotas-border bg-cuotas-surface-2 scroll-mt-6 rounded-[var(--radius-cuotas-md)] border p-6"
            >
                <h2 class="mb-1 text-lg font-semibold">
                    {{ t('profile.currency') }}
                </h2>
                <p class="text-cuotas-muted mb-5 text-[13px]">
                    {{ t('profile.currency_hint') }}
                </p>
                <Form
                    action="/perfil/moneda"
                    method="patch"
                    v-slot="{ errors, processing }"
                    class="grid gap-4"
                >
                    <div class="grid grid-cols-2 gap-3">
                        <label class="relative">
                            <input
                                v-model="preferredCurrency"
                                type="radio"
                                name="preferred_currency"
                                value="UYU"
                                class="peer absolute inset-0 opacity-0"
                            />
                            <div
                                class="border-cuotas-border peer-checked:border-cuotas-bg-deep rounded-[var(--radius-cuotas-sm)] border bg-white p-4 peer-checked:bg-[#f2fad0]"
                            >
                                <span
                                    class="font-[family-name:var(--font-head)] text-xl font-bold"
                                    >$</span
                                >
                                <p class="text-[13px]">
                                    {{ t('onboarding.uyu') }}
                                </p>
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
                                class="border-cuotas-border peer-checked:border-cuotas-bg-deep rounded-[var(--radius-cuotas-sm)] border bg-white p-4 peer-checked:bg-[#f2fad0]"
                            >
                                <span
                                    class="font-[family-name:var(--font-head)] text-xl font-bold"
                                    >U$</span
                                >
                                <p class="text-[13px]">
                                    {{ t('onboarding.usd') }}
                                </p>
                            </div>
                        </label>
                    </div>
                    <InputError :message="errors.preferred_currency" />
                    <div
                        class="border-cuotas-border flex items-center justify-between border-t py-3"
                    >
                        <span class="text-sm">{{
                            t('profile.show_both')
                        }}</span>
                        <input
                            type="hidden"
                            name="show_both_currencies"
                            :value="showBoth ? 1 : 0"
                        />
                        <Switch v-model="showBoth" />
                    </div>
                    <div class="grid gap-1.5">
                        <label class="text-cuotas-muted text-xs">
                            {{
                                user.exchange_rate_automatic
                                    ? t('profile.fx_auto')
                                    : t('profile.fx_manual')
                            }}
                        </label>
                        <input
                            name="usd_uyu_rate"
                            type="number"
                            step="0.0001"
                            :value="user.usd_uyu_rate ?? ''"
                            :disabled="user.exchange_rate_automatic"
                            class="cuotas-input disabled:bg-cuotas-border/40"
                            placeholder="Ej. 40.50"
                        />
                        <InputError :message="errors.usd_uyu_rate" />
                        <p
                            v-if="user.exchange_rate_automatic"
                            class="text-cuotas-muted text-xs"
                        >
                            {{
                                t('profile.fx_updated', {
                                    date: user.exchange_rate_date ?? '',
                                })
                            }}
                            <a
                                href="https://datosuruguay.com/dolar?utm_source=api&utm_medium=attribution&utm_campaign=backlinks"
                                target="_blank"
                                rel="noreferrer"
                                class="text-cuotas-ink font-semibold"
                            >
                                datosUruguay.com </a
                            >.
                        </p>
                    </div>
                    <button
                        type="submit"
                        class="bg-cuotas-ink w-full cursor-pointer rounded-full px-4 py-2 text-sm font-semibold text-white disabled:cursor-not-allowed disabled:opacity-60 sm:w-auto sm:justify-self-end"
                        :disabled="processing"
                    >
                        {{ t('profile.save_currency') }}
                    </button>
                </Form>
            </section>

            <section
                v-if="features.reminders || push.enabled"
                id="notificaciones"
                class="border-cuotas-border bg-cuotas-surface-2 scroll-mt-6 rounded-[var(--radius-cuotas-md)] border p-6"
            >
                <h2 class="mb-1 text-lg font-semibold">
                    {{ t('profile.notifications') }}
                </h2>
                <p class="text-cuotas-muted mb-5 text-[13px]">
                    {{ t('profile.notifications_hint') }}
                </p>
                <Form
                    v-if="features.reminders"
                    action="/perfil/notificaciones"
                    method="patch"
                    v-slot="{ errors, processing }"
                    class="grid gap-4"
                >
                    <div
                        class="border-cuotas-border flex items-center justify-between border-t py-3"
                    >
                        <span class="text-sm">{{
                            t('profile.reminders')
                        }}</span>
                        <input
                            type="hidden"
                            name="reminders_enabled"
                            :value="remindersEnabled ? 1 : 0"
                        />
                        <Switch v-model="remindersEnabled" />
                    </div>
                    <InputError :message="errors.reminders_enabled" />
                    <button
                        type="submit"
                        class="bg-cuotas-ink w-full cursor-pointer rounded-full px-4 py-2 text-sm font-semibold text-white disabled:cursor-not-allowed disabled:opacity-60 sm:w-auto sm:justify-self-end"
                        :disabled="processing"
                    >
                        {{ t('profile.save_notifications') }}
                    </button>
                </Form>

                <div
                    v-if="push.enabled && pushSupported"
                    class="border-cuotas-border mt-6 border-t pt-5"
                >
                    <p class="mb-1 text-sm font-semibold">
                        {{ t('profile.push_title') }}
                    </p>
                    <p class="text-cuotas-muted mb-4 text-[13px]">
                        {{ t('profile.push_hint') }}
                    </p>
                    <button
                        v-if="!pushSubscribed"
                        type="button"
                        class="bg-cuotas-ink rounded-full px-4 py-2 text-sm font-semibold text-white disabled:opacity-60"
                        :disabled="pushLoading"
                        @click="subscribePush"
                    >
                        {{
                            pushLoading
                                ? t('profile.push_enabling')
                                : t('profile.push_enable')
                        }}
                    </button>
                    <button
                        v-else
                        type="button"
                        class="border-cuotas-ink rounded-full border px-4 py-2 text-sm font-semibold disabled:opacity-60"
                        :disabled="pushLoading"
                        @click="unsubscribePush"
                    >
                        {{
                            pushLoading
                                ? t('profile.push_disabling')
                                : t('profile.push_disable')
                        }}
                    </button>
                    <p v-if="pushError" class="text-cuotas-red mt-3 text-xs">
                        {{ pushError }}
                    </p>
                </div>
            </section>

            <section
                id="suscripcion"
                class="border-cuotas-border bg-cuotas-surface-2 scroll-mt-6 rounded-[var(--radius-cuotas-md)] border p-6"
            >
                <h2 class="mb-1 text-lg font-semibold">
                    {{ t('profile.subscription') }}
                </h2>
                <div
                    class="bg-cuotas-ink mt-4 flex flex-wrap items-center justify-between gap-3 rounded-[var(--radius-cuotas-md)] p-5 text-white"
                >
                    <div>
                        <p
                            class="flex items-center gap-2 font-[family-name:var(--font-head)] text-xl font-bold"
                        >
                            {{ t('billing.plan', { name: plan.name }) }}
                            <span
                                class="bg-cuotas-bg text-cuotas-ink rounded-full px-2 py-0.5 text-[10px] font-bold"
                                >{{ t('profile.current') }}</span
                            >
                        </p>
                        <p class="mt-1 text-xs text-[#b9b9b9]">
                            {{
                                plan.is_free
                                    ? t('pricing.free.description')
                                    : t('pricing.pro.description')
                            }}
                        </p>
                    </div>
                    <div class="text-right">
                        <p
                            class="font-[family-name:var(--font-head)] text-xl font-bold"
                        >
                            $ {{ plan.price }}
                        </p>
                        <p class="text-[11px] text-[#b9b9b9]">
                            {{ t('common.per_month') }}
                        </p>
                    </div>
                </div>
                <div class="mt-4 flex flex-wrap gap-2">
                    <Link
                        :href="pricing()"
                        class="bg-cuotas-ink rounded-full px-4 py-2 text-sm font-semibold text-white"
                    >
                        {{ t('common.see_plans') }}
                    </Link>
                    <Link
                        :href="billing()"
                        class="border-cuotas-ink rounded-full border px-4 py-2 text-sm font-semibold"
                    >
                        {{ t('billing.title') }}
                    </Link>
                </div>
            </section>

            <section
                id="cuenta"
                class="border-cuotas-border bg-cuotas-surface-2 scroll-mt-6 rounded-[var(--radius-cuotas-md)] border p-6"
            >
                <h2 class="text-cuotas-red mb-1 text-lg font-semibold">
                    {{ t('profile.danger_zone') }}
                </h2>
                <p class="text-cuotas-muted mb-5 text-[13px]">
                    {{ t('profile.delete_account_hint') }}
                </p>
                <Dialog>
                    <DialogTrigger as-child>
                        <button
                            type="button"
                            class="border-cuotas-red text-cuotas-red cursor-pointer rounded-full border px-4 py-2 text-sm font-semibold"
                        >
                            {{ t('profile.delete_account') }}
                        </button>
                    </DialogTrigger>
                    <DialogContent class="rounded-[var(--radius-cuotas-lg)]">
                        <Form
                            v-bind="destroyAccount.form()"
                            reset-on-success
                            v-slot="{ errors, processing, reset, clearErrors }"
                            class="grid gap-4"
                        >
                            <DialogHeader>
                                <DialogTitle>{{
                                    t('profile.delete_confirm_title')
                                }}</DialogTitle>
                                <DialogDescription>
                                    {{ t('profile.delete_confirm_body') }}
                                </DialogDescription>
                            </DialogHeader>
                            <div class="grid gap-1.5">
                                <label class="text-cuotas-muted text-xs">{{
                                    t('profile.delete_password')
                                }}</label>
                                <input
                                    name="password"
                                    type="password"
                                    required
                                    autocomplete="current-password"
                                    class="cuotas-input"
                                />
                                <InputError :message="errors.password" />
                            </div>
                            <DialogFooter class="gap-2">
                                <DialogClose as-child>
                                    <button
                                        type="button"
                                        class="border-cuotas-border cursor-pointer rounded-full border px-4 py-2 text-sm font-semibold"
                                        @click="
                                            () => {
                                                clearErrors();
                                                reset();
                                            }
                                        "
                                    >
                                        {{ t('common.cancel') }}
                                    </button>
                                </DialogClose>
                                <button
                                    type="submit"
                                    :disabled="processing"
                                    class="bg-cuotas-red cursor-pointer rounded-full px-4 py-2 text-sm font-semibold text-white disabled:opacity-60"
                                >
                                    {{ t('profile.delete_confirm_button') }}
                                </button>
                            </DialogFooter>
                        </Form>
                    </DialogContent>
                </Dialog>
            </section>
        </div>
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
