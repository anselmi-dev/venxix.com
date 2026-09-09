<script setup lang="ts">
import { Head, setLayoutProps } from '@inertiajs/vue3';
import { useTrans } from '@/composables/useTrans';

const props = defineProps<{
    slug: 'terminos' | 'privacidad';
    title: string;
    updated: string;
    html: string;
    toc: { id: string; label: string }[];
}>();

const { t } = useTrans();

setLayoutProps({
    variant: 'legal',
    active: props.slug === 'privacidad' ? 'privacy' : 'terms',
});
</script>

<template>
    <Head :title="title" />

    <p class="mb-2 text-[13px] font-medium text-cuotas-muted">
        {{ t('legal.kicker') }}
    </p>
    <h1 class="mb-2 text-3xl font-semibold">{{ title }}</h1>
    <p class="mb-8 text-xs text-cuotas-muted">
        {{ t('legal.updated', { date: updated }) }}
    </p>

    <div class="grid items-start gap-9 lg:grid-cols-[220px_1fr]">
        <nav class="sticky top-7 hidden flex-col gap-1 lg:flex">
            <a
                v-for="item in toc"
                :key="item.id"
                :href="`#${item.id}`"
                class="rounded-lg px-2.5 py-1.5 text-[13px] text-cuotas-muted hover:bg-cuotas-surface-2 hover:text-cuotas-ink"
            >
                {{ item.label }}
            </a>
        </nav>
        <article
            class="legal-body max-w-[70ch] text-sm leading-relaxed text-[#3a3a3a]"
            v-html="html"
        />
    </div>
</template>

<style scoped>
.legal-body :deep(h2) {
    scroll-margin-top: 24px;
    margin: 30px 0 10px;
    font-size: 17px;
    font-weight: 600;
    color: var(--color-cuotas-ink);
}

.legal-body :deep(h2:first-child) {
    margin-top: 0;
}

.legal-body :deep(p) {
    margin-bottom: 12px;
}

.legal-body :deep(ul) {
    margin: 0 0 12px;
    padding-left: 20px;
}

.legal-body :deep(li) {
    margin-bottom: 6px;
}
</style>
