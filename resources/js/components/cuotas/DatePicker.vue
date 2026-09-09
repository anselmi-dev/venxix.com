<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { parseDate, type DateValue } from '@internationalized/date';
import {
    Calendar,
    ChevronLeft,
    ChevronRight,
} from '@animated-color-icons/lucide-vue';
import { ref, shallowRef, watch } from 'vue';
import {
    DatePickerCalendar,
    DatePickerCell,
    DatePickerCellTrigger,
    DatePickerContent,
    DatePickerField,
    DatePickerGrid,
    DatePickerGridBody,
    DatePickerGridHead,
    DatePickerGridRow,
    DatePickerHeadCell,
    DatePickerHeader,
    DatePickerHeading,
    DatePickerInput,
    DatePickerNext,
    DatePickerPrev,
    DatePickerRoot,
    DatePickerTrigger,
} from 'reka-ui';

const props = withDefaults(
    defineProps<{
        name: string;
        id?: string;
        required?: boolean;
        modelValue?: string | null;
    }>(),
    {
        required: false,
        modelValue: null,
    },
);

const emit = defineEmits<{
    'update:modelValue': [value: string | null];
}>();

const page = usePage();

const internalValue = shallowRef<DateValue | undefined>(
    props.modelValue ? parseDate(props.modelValue) : undefined,
);
const isoValue = ref(props.modelValue ?? '');

watch(
    () => props.modelValue,
    (value) => {
        internalValue.value = value ? parseDate(value) : undefined;
        isoValue.value = value ?? '';
    },
);

function handleUpdate(value: DateValue | undefined): void {
    internalValue.value = value;
    isoValue.value = value ? value.toString() : '';
    emit('update:modelValue', isoValue.value === '' ? null : isoValue.value);
}
</script>

<template>
    <DatePickerRoot
        :model-value="internalValue"
        :locale="page.props.locale === 'en' ? 'en-US' : 'es-UY'"
        :week-starts-on="1"
        granularity="day"
        close-on-select
        @update:model-value="handleUpdate"
    >
        <input
            type="hidden"
            :name="name"
            :required="required"
            :value="isoValue"
        />
        <div
            class="border-cuotas-border bg-cuotas-surface-2 focus-within:border-cuotas-ink flex items-center gap-2 rounded-[var(--radius-cuotas-sm)] border-[1.5px] px-3.5 py-3 text-sm transition-colors"
        >
            <DatePickerField v-slot="{ segments }" :id="id">
                <span class="inline-flex items-center">
                    <DatePickerInput
                        v-for="segment in segments"
                        :key="segment.part"
                        :part="segment.part"
                        class="data-[placeholder]:text-cuotas-muted focus:bg-cuotas-bg-deep/50 rounded px-0.5 tabular-nums outline-none"
                    >
                        {{ segment.value }}
                    </DatePickerInput>
                </span>
            </DatePickerField>
            <DatePickerTrigger
                class="al-icon-wrapper text-cuotas-muted hover:text-cuotas-ink ml-auto shrink-0 cursor-pointer"
            >
                <Calendar class="h-4 w-4" />
            </DatePickerTrigger>
        </div>

        <DatePickerContent
            :side-offset="8"
            class="border-cuotas-border animate-in fade-in-0 zoom-in-95 z-50 rounded-[var(--radius-cuotas-lg)] border bg-white p-4 shadow-[var(--shadow-cuotas)]"
        >
            <DatePickerCalendar v-slot="{ weekDays, grid }">
                <DatePickerHeader
                    class="mb-3 flex items-center justify-between"
                >
                    <DatePickerPrev
                        class="al-icon-wrapper hover:bg-cuotas-surface-2 flex h-7 w-7 cursor-pointer items-center justify-center rounded-full transition-colors disabled:cursor-not-allowed disabled:opacity-30"
                    >
                        <ChevronLeft class="h-4 w-4" />
                    </DatePickerPrev>
                    <DatePickerHeading
                        class="text-sm font-semibold capitalize"
                    />
                    <DatePickerNext
                        class="al-icon-wrapper hover:bg-cuotas-surface-2 flex h-7 w-7 cursor-pointer items-center justify-center rounded-full transition-colors disabled:cursor-not-allowed disabled:opacity-30"
                    >
                        <ChevronRight class="h-4 w-4" />
                    </DatePickerNext>
                </DatePickerHeader>

                <DatePickerGrid
                    v-for="month in grid"
                    :key="month.value.toString()"
                    class="w-full border-collapse select-none"
                >
                    <DatePickerGridHead>
                        <DatePickerGridRow class="flex justify-between">
                            <DatePickerHeadCell
                                v-for="day in weekDays"
                                :key="day"
                                class="text-cuotas-muted flex h-8 w-8 items-center justify-center text-[11px] font-medium capitalize"
                            >
                                {{ day }}
                            </DatePickerHeadCell>
                        </DatePickerGridRow>
                    </DatePickerGridHead>
                    <DatePickerGridBody>
                        <DatePickerGridRow
                            v-for="(weekDates, index) in month.rows"
                            :key="`weekDate-${index}`"
                            class="mt-1 flex w-full justify-between"
                        >
                            <DatePickerCell
                                v-for="weekDate in weekDates"
                                :key="weekDate.toString()"
                                :date="weekDate"
                                class="p-0 text-center"
                            >
                                <DatePickerCellTrigger
                                    :day="weekDate"
                                    :month="month.value"
                                    class="hover:bg-cuotas-surface-2 data-[today]:border-cuotas-bg-deep data-[selected]:bg-cuotas-ink data-[outside-view]:text-cuotas-muted/40 flex h-8 w-8 cursor-pointer items-center justify-center rounded-full text-xs transition-colors data-[disabled]:opacity-30 data-[selected]:font-semibold data-[selected]:text-white data-[today]:border"
                                />
                            </DatePickerCell>
                        </DatePickerGridRow>
                    </DatePickerGridBody>
                </DatePickerGrid>
            </DatePickerCalendar>
        </DatePickerContent>
    </DatePickerRoot>
</template>
