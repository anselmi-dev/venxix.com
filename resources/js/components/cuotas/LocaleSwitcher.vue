<script setup lang="ts">
import { Form, usePage } from '@inertiajs/vue3';
import { useTrans } from '@/composables/useTrans';
import { update } from '@/routes/locale';

const page = usePage();
const { t } = useTrans();
</script>

<template>
    <div
        class="border-cuotas-border inline-flex items-center rounded-full border p-0.5 text-[11px] font-semibold"
        role="group"
        :aria-label="t('locale.label')"
    >
        <Form
            v-for="locale in page.props.availableLocales"
            :key="locale"
            :action="update.url()"
            method="post"
            class="inline"
        >
            <input type="hidden" name="locale" :value="locale" />
            <button
                type="submit"
                class="cursor-pointer rounded-full px-2.5 py-1 transition-colors duration-200 disabled:cursor-default"
                :class="
                    page.props.locale === locale
                        ? 'bg-cuotas-ink text-white'
                        : 'text-cuotas-muted hover:text-cuotas-ink'
                "
                :disabled="page.props.locale === locale"
                :aria-pressed="page.props.locale === locale"
            >
                {{ t(`locale.${locale}`) }}
            </button>
        </Form>
    </div>
</template>
