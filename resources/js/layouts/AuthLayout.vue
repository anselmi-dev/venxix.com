<script setup lang="ts">
import AuthCuotasLayout from '@/layouts/auth/AuthCuotasLayout.vue';

const { title = '', description = '' } = defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <AuthCuotasLayout :title="title" :description="description">
        <slot />
    </AuthCuotasLayout>
</template>
