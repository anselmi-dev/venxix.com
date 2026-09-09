<script setup lang="ts">
import type { TooltipContentEmits, TooltipContentProps } from 'reka-ui';
import type { HTMLAttributes } from 'vue';
import {
    TooltipArrow,
    TooltipContent,
    TooltipPortal,
    useForwardPropsEmits,
} from 'reka-ui';
import { cn } from '@/lib/utils';

const props = withDefaults(
    defineProps<TooltipContentProps & { class?: HTMLAttributes['class'] }>(),
    {
        sideOffset: 8,
    },
);
const emits = defineEmits<TooltipContentEmits>();

const delegatedProps = props;
const forwarded = useForwardPropsEmits(delegatedProps, emits);
</script>

<template>
    <TooltipPortal>
        <TooltipContent
            data-slot="tooltip-content"
            v-bind="forwarded"
            :class="
                cn(
                    'bg-cuotas-ink text-white animate-in fade-in-0 zoom-in-95 data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=closed]:zoom-out-95 z-50 max-w-[220px] rounded-[var(--radius-cuotas-sm)] px-3 py-2 text-xs leading-relaxed shadow-[var(--shadow-cuotas-sm)]',
                    props.class,
                )
            "
        >
            <slot />
            <TooltipArrow class="fill-cuotas-ink" :width="8" :height="4" />
        </TooltipContent>
    </TooltipPortal>
</template>
