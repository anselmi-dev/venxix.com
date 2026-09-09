<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { Archive, ArchiveRestore, Trash2 } from '@animated-color-icons/lucide-vue';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { useTrans } from '@/composables/useTrans';
import { archive, destroy, restore } from '@/routes/notes';

export type NoteItem = {
    id: number;
    body: string;
    is_flagged: boolean;
    archived_at: string | null;
    created_at: string;
};

type TimelineGroup = {
    label: string;
    items: NoteItem[];
};

const props = defineProps<{
    notes: NoteItem[];
}>();

const { t } = useTrans();
const page = usePage();

const activeTab = ref<'active' | 'archived'>('active');
const noteToDelete = ref<NoteItem | null>(null);

const locale = computed(() =>
    page.props.locale === 'en' ? 'en-US' : 'es-UY',
);

const activeCount = computed(
    () => props.notes.filter((note) => note.archived_at === null).length,
);
const archivedCount = computed(
    () => props.notes.filter((note) => note.archived_at !== null).length,
);

const filteredNotes = computed(() =>
    props.notes
        .filter((note) =>
            activeTab.value === 'archived'
                ? note.archived_at !== null
                : note.archived_at === null,
        )
        .slice()
        .sort(
            (a, b) =>
                new Date(b.created_at).getTime() -
                new Date(a.created_at).getTime(),
        ),
);

const groupedNotes = computed<TimelineGroup[]>(() => {
    const dateFormatter = new Intl.DateTimeFormat(locale.value, {
        dateStyle: 'long',
    });
    const groups: { key: string; date: Date; items: NoteItem[] }[] = [];

    for (const note of filteredNotes.value) {
        const date = new Date(note.created_at);
        const key = `${date.getFullYear()}-${date.getMonth()}-${date.getDate()}`;
        let group = groups.find((candidate) => candidate.key === key);

        if (!group) {
            group = { key, date, items: [] };
            groups.push(group);
        }

        group.items.push(note);
    }

    return groups.map((group) => ({
        label: dateFormatter.format(group.date),
        items: group.items,
    }));
});

function noteTime(iso: string): string {
    return new Intl.DateTimeFormat(locale.value, {
        hour: '2-digit',
        minute: '2-digit',
        hour12: false,
    }).format(new Date(iso));
}

function archiveNote(note: NoteItem): void {
    router.patch(archive.url(note.id), {}, { preserveScroll: true });
}

function restoreNote(note: NoteItem): void {
    router.patch(restore.url(note.id), {}, { preserveScroll: true });
}

function askDelete(note: NoteItem): void {
    noteToDelete.value = note;
}

function performDelete(): void {
    if (!noteToDelete.value) {
        return;
    }

    router.delete(destroy.url(noteToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            noteToDelete.value = null;
        },
    });
}
</script>

<template>
    <div>
        <div
            class="border-cuotas-border bg-cuotas-surface-2 mb-3 flex w-fit gap-1 rounded-full border p-1"
        >
            <button
                type="button"
                class="cursor-pointer rounded-full px-3 py-1.5 text-xs font-semibold"
                :class="
                    activeTab === 'active'
                        ? 'bg-cuotas-ink text-white'
                        : 'text-cuotas-muted'
                "
                @click="activeTab = 'active'"
            >
                {{ t('notes.tab_active') }} ({{ activeCount }})
            </button>
            <button
                type="button"
                class="cursor-pointer rounded-full px-3 py-1.5 text-xs font-semibold"
                :class="
                    activeTab === 'archived'
                        ? 'bg-cuotas-ink text-white'
                        : 'text-cuotas-muted'
                "
                @click="activeTab = 'archived'"
            >
                {{ t('notes.tab_archived') }} ({{ archivedCount }})
            </button>
        </div>

        <p
            v-if="groupedNotes.length === 0"
            class="text-cuotas-muted py-8 text-center text-[13px]"
        >
            {{
                activeTab === 'archived'
                    ? t('notes.empty_archived')
                    : t('notes.empty_active')
            }}
        </p>

        <div v-else class="flex flex-col">
            <div
                v-for="group in groupedNotes"
                :key="group.label"
                class="mb-2 last:mb-0"
            >
                <p
                    class="text-cuotas-muted mt-4 mb-3 text-xs font-bold tracking-wide uppercase first:mt-0"
                >
                    {{ group.label }}
                </p>
                <div class="flex flex-col">
                    <div
                        v-for="(note, index) in group.items"
                        :key="note.id"
                        class="flex gap-3.5"
                    >
                        <span
                            class="text-cuotas-muted w-[46px] shrink-0 pt-0.5 text-right font-[family-name:var(--font-head)] text-xs font-semibold"
                            >{{ noteTime(note.created_at) }}</span
                        >
                        <div
                            class="flex w-[18px] shrink-0 flex-col items-center"
                        >
                            <span
                                class="mt-1 h-2.5 w-2.5 shrink-0 rounded-full border-2 bg-white"
                                :class="
                                    note.is_flagged
                                        ? 'border-cuotas-red'
                                        : 'border-cuotas-border'
                                "
                            />
                            <span
                                v-if="index < group.items.length - 1"
                                class="bg-cuotas-border my-1 min-h-3.5 w-px flex-1"
                            />
                        </div>
                        <div class="min-w-0 flex-1 pb-5 last:pb-0">
                            <div
                                class="border-cuotas-border flex items-start gap-3 rounded-[var(--radius-cuotas-md)] border bg-white px-4 py-3.5"
                            >
                                <p
                                    class="min-w-0 flex-1 text-[13px] leading-relaxed break-words"
                                >
                                    {{ note.body }}
                                </p>
                                <div class="flex shrink-0 gap-1.5">
                                    <button
                                        v-if="note.archived_at === null"
                                        type="button"
                                        :aria-label="t('notes.archive')"
                                        class="al-icon-wrapper text-cuotas-muted hover:text-cuotas-ink cursor-pointer"
                                        @click="archiveNote(note)"
                                    >
                                        <Archive class="h-3.5 w-3.5" />
                                    </button>
                                    <button
                                        v-else
                                        type="button"
                                        :aria-label="t('notes.restore')"
                                        class="al-icon-wrapper text-cuotas-muted hover:text-cuotas-ink cursor-pointer"
                                        @click="restoreNote(note)"
                                    >
                                        <ArchiveRestore class="h-3.5 w-3.5" />
                                    </button>
                                    <button
                                        type="button"
                                        :aria-label="t('notes.delete')"
                                        class="al-icon-wrapper text-cuotas-muted hover:text-cuotas-red cursor-pointer"
                                        @click="askDelete(note)"
                                    >
                                        <Trash2 class="h-3.5 w-3.5" />
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <Dialog
            :open="noteToDelete !== null"
            @update:open="
                (value) => {
                    if (!value) {
                        noteToDelete = null;
                    }
                }
            "
        >
            <DialogContent class="sm:max-w-sm">
                <DialogHeader>
                    <DialogTitle>{{
                        t('notes.delete_confirm_title')
                    }}</DialogTitle>
                </DialogHeader>
                <p class="text-cuotas-muted text-sm">
                    {{ t('notes.delete_confirm_body') }}
                </p>
                <DialogFooter>
                    <button
                        type="button"
                        class="border-cuotas-border cursor-pointer rounded-full border px-4 py-2 text-sm font-semibold"
                        @click="noteToDelete = null"
                    >
                        {{ t('notes.delete_confirm_cancel') }}
                    </button>
                    <button
                        type="button"
                        class="bg-cuotas-red cursor-pointer rounded-full px-4 py-2 text-sm font-semibold text-white"
                        @click="performDelete"
                    >
                        {{ t('notes.delete_confirm_action') }}
                    </button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
