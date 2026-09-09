<script setup lang="ts">
import type { TooltipTriggerProps } from 'reka-ui';
import { TooltipTrigger, useForwardExpose } from 'reka-ui';

const props = defineProps<TooltipTriggerProps>();

useForwardExpose();
</script>

<template>
    <TooltipTrigger data-slot="tooltip-trigger" v-bind="props">
        <slot />
    </TooltipTrigger>
</template>
