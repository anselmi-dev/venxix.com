<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import InputError from '@/components/InputError.vue';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { useTrans } from '@/composables/useTrans';
import { store as storeNote } from '@/routes/notes';

const props = defineProps<{
    maxLength: number;
}>();

const open = defineModel<boolean>('open', { default: false });

const { t } = useTrans();

const noteBody = ref('');
const charCount = computed(() => noteBody.value.length);
const isAtLimit = computed(() => charCount.value >= props.maxLength);

function close(): void {
    open.value = false;
    noteBody.value = '';
}
</script>

<template>
    <Dialog :open="open" @update:open="open = $event">
        <DialogContent
            class="max-h-[calc(100dvh-2rem)] overflow-y-auto rounded-[var(--radius-cuotas-lg)] sm:max-w-lg"
        >
            <DialogHeader>
                <DialogTitle>{{ t('notes.add') }}</DialogTitle>
            </DialogHeader>

            <Form
                v-bind="storeNote.form()"
                v-slot="{ errors, processing }"
                class="grid gap-2"
                @success="close"
            >
                <textarea
                    v-model="noteBody"
                    name="body"
                    rows="3"
                    required
                    autofocus
                    :maxlength="maxLength"
                    :placeholder="t('notes.placeholder')"
                    class="cuotas-input resize-none"
                />
                <div class="flex items-center justify-between gap-2">
                    <InputError :message="errors.body" />
                    <span
                        class="ml-auto shrink-0 text-[11px] font-medium"
                        :class="
                            isAtLimit ? 'text-cuotas-red' : 'text-cuotas-muted'
                        "
                    >
                        {{
                            t('notes.char_count', {
                                count: charCount,
                                max: maxLength,
                            })
                        }}
                    </span>
                </div>
                <div class="mt-1 flex justify-end gap-2">
                    <button
                        type="button"
                        class="border-cuotas-border cursor-pointer rounded-full border px-4 py-2 text-sm font-semibold"
                        @click="close"
                    >
                        {{ t('common.cancel') }}
                    </button>
                    <button
                        type="submit"
                        :disabled="processing || charCount === 0"
                        class="bg-cuotas-ink cursor-pointer rounded-full px-4 py-2 text-sm font-semibold text-white transition-transform duration-200 hover:scale-[1.02] active:scale-95 disabled:cursor-not-allowed disabled:opacity-60"
                    >
                        {{ t('notes.add') }}
                    </button>
                </div>
            </Form>
        </DialogContent>
    </Dialog>
</template>

<style scoped>
.cuotas-input {
    width: 100%;
    border-radius: var(--radius-cuotas-sm);
    border: 1.5px solid var(--color-cuotas-border);
    background: var(--color-cuotas-surface-2);
    padding: 0.625rem 0.75rem;
    font-size: 0.875rem;
}
</style>
