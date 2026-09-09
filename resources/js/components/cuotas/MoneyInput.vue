<script setup lang="ts">
import { computed, ref, watch } from 'vue';

const props = withDefaults(
    defineProps<{
        name: string;
        id?: string;
        currency?: string;
        required?: boolean;
        placeholder?: string;
        modelValue?: number | string | null;
    }>(),
    {
        currency: 'UYU',
        required: false,
        placeholder: '0',
        modelValue: null,
    },
);

const emit = defineEmits<{
    'update:modelValue': [value: number | null];
}>();

const symbol = computed(() => (props.currency === 'USD' ? 'U$' : '$'));

function sanitize(raw: string): string {
    let value = raw.replace(/[^\d,]/g, '');
    const parts = value.split(',');

    if (parts.length > 2) {
        value = `${parts[0]},${parts.slice(1).join('')}`;
    }

    return value;
}

function formatDisplay(raw: string): string {
    const [rawInt, rawDec] = sanitize(raw).split(',');
    const cleanInt = (rawInt ?? '').replace(/^0+(?=\d)/, '');
    const grouped = cleanInt.replace(/\B(?=(\d{3})+(?!\d))/g, '.');

    if (rawDec !== undefined) {
        return `${grouped || '0'},${rawDec.slice(0, 2)}`;
    }

    return grouped;
}

function toNumericString(display: string): string {
    const [rawInt, rawDec] = sanitize(display).split(',');
    const cleanInt = rawInt || '0';

    return rawDec !== undefined ? `${cleanInt}.${rawDec}` : cleanInt;
}

function initialDisplay(): string {
    if (props.modelValue === null || props.modelValue === '') {
        return '';
    }

    const amount = Number(props.modelValue);

    if (Number.isNaN(amount)) {
        return '';
    }

    return amount.toLocaleString('es-UY', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
    });
}

const display = ref(initialDisplay());
const numeric = ref(display.value ? toNumericString(display.value) : '');

watch(
    () => props.modelValue,
    () => {
        display.value = initialDisplay();
        numeric.value = display.value ? toNumericString(display.value) : '';
    },
);

function handleInput(event: Event): void {
    const target = event.target as HTMLInputElement;

    display.value = formatDisplay(target.value);
    numeric.value = display.value ? toNumericString(display.value) : '';

    emit(
        'update:modelValue',
        numeric.value === '' ? null : Number(numeric.value),
    );
}
</script>

<template>
    <div
        class="border-cuotas-border bg-cuotas-surface-2 focus-within:border-cuotas-ink flex items-center gap-1.5 rounded-[var(--radius-cuotas-sm)] border-[1.5px] px-3.5 py-3 text-sm transition-colors"
    >
        <span class="text-cuotas-muted font-semibold">{{ symbol }}</span>
        <input
            :id="id"
            type="text"
            inputmode="decimal"
            autocomplete="off"
            :placeholder="placeholder"
            :required="required"
            :value="display"
            class="w-full min-w-0 bg-transparent outline-none"
            @input="handleInput"
        />
        <input type="hidden" :name="name" :value="numeric" />
    </div>
</template>
