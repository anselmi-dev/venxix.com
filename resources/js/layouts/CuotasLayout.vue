<script setup lang="ts">
import CuotasTopNav from '@/components/cuotas/CuotasTopNav.vue';

defineProps<{
    active?: 'panel' | 'summary' | 'profile';
    narrow?: boolean;
}>();
</script>

<template>
    <div class="min-h-svh bg-cuotas-bg p-3.5 sm:p-7">
        <div
            class="mx-auto rounded-[var(--radius-cuotas-xl)] bg-cuotas-surface p-6 shadow-[var(--shadow-cuotas)] sm:p-10"
            :class="narrow ? 'max-w-md' : 'max-w-[1120px]'"
        >
            <CuotasTopNav v-if="!narrow" :active="active" />
            <slot />
        </div>
    </div>
</template>
