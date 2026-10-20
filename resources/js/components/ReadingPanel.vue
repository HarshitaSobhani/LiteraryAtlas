<script setup lang="ts">
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { STATUS_LABELS } from '@/lib/format';
import type { Book, ReadingEntry, ReadingStatus, SharedData } from '@/types';
import { Link, router, useForm, usePage } from '@inertiajs/vue3';
import { Star } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

const props = defineProps<{ book: Book; entry: ReadingEntry | null }>();
const page = usePage<SharedData>();
const user = computed(() => page.props.auth.user);

const addForm = useForm({ book_id: props.book.id, status: 'want_to_read' as ReadingStatus });
const add = () => addForm.post(route('library.store'), { preserveScroll: true });

const editForm = useForm({
    status: (props.entry?.status ?? 'want_to_read') as ReadingStatus,
    progress: props.entry?.progress ?? 0,
    rating: props.entry?.rating ?? (null as number | null),
    notes: props.entry?.notes ?? '',
});
watch(
    () => props.entry,
    (e) => {
        if (!e) return;
        editForm.defaults({ status: e.status, progress: e.progress, rating: e.rating, notes: e.notes ?? '' });
        editForm.reset();
    },
);
const save = () => props.entry && editForm.patch(route('library.update', props.entry.id), { preserveScroll: true });

const confirmOpen = ref(false);
const remove = () => {
    if (!props.entry) return;
    router.delete(route('library.destroy', props.entry.id), { preserveScroll: true, onFinish: () => (confirmOpen.value = false) });
};
const showProgress = computed(() => editForm.status === 'reading' || editForm.status === 'on_hold');
</script>

<template>
    <section class="paper-card p-5" aria-labelledby="reading-heading">
        <h2 id="reading-heading" class="font-serif text-lg font-semibold">Your reading</h2>

        <div v-if="!user" class="mt-3 text-sm">
            <p class="text-muted-foreground">Sign in to save this book, track progress, rate it and keep private notes.</p>
            <div class="mt-4 flex gap-2">
                <Button as-child size="sm"><Link :href="route('login')">Log in</Link></Button>
                <Button as-child size="sm" variant="outline"><Link :href="route('register')">Create account</Link></Button>
            </div>
        </div>

        <form v-else-if="!entry" class="mt-3 space-y-3" @submit.prevent="add">
            <label for="add-status" class="block text-sm font-medium">Add to my library as</label>
            <select id="add-status" v-model="addForm.status" class="field">
                <option v-for="(label, key) in STATUS_LABELS" :key="key" :value="key">{{ label }}</option>
            </select>
            <InputError :message="addForm.errors.status || addForm.errors.book_id" />
            <Button type="submit" class="w-full" :disabled="addForm.processing">Add to library</Button>
        </form>

        <form v-else class="mt-3 space-y-4" @submit.prevent="save">
            <div>
                <label for="status" class="mb-1 block text-sm font-medium">Status</label>
                <select id="status" v-model="editForm.status" class="field">
                    <option v-for="(label, key) in STATUS_LABELS" :key="key" :value="key">{{ label }}</option>
                </select>
                <InputError :message="editForm.errors.status" />
            </div>
            <div v-if="showProgress">
                <label for="progress" class="mb-1 block text-sm font-medium">Progress: {{ editForm.progress }}%</label>
                <input
                    id="progress"
                    v-model.number="editForm.progress"
                    type="range"
                    min="0"
                    max="100"
                    step="5"
                    class="w-full accent-[hsl(var(--primary))]"
                />
                <InputError :message="editForm.errors.progress" />
            </div>
            <fieldset>
                <legend class="mb-1 text-sm font-medium">Your rating</legend>
                <div class="flex items-center gap-1">
                    <button
                        v-for="n in 5"
                        :key="n"
                        type="button"
                        class="rounded p-0.5 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                        :aria-label="`${n} star${n > 1 ? 's' : ''}`"
                        :aria-pressed="editForm.rating === n"
                        @click="editForm.rating = editForm.rating === n ? null : n"
                    >
                        <Star class="size-6" :class="(editForm.rating ?? 0) >= n ? 'fill-terracotta text-terracotta' : 'text-muted-foreground'" />
                    </button>
                </div>
                <InputError :message="editForm.errors.rating" />
            </fieldset>
            <div>
                <label for="notes" class="mb-1 block text-sm font-medium">Private notes</label>
                <textarea
                    id="notes"
                    v-model="editForm.notes"
                    rows="4"
                    maxlength="5000"
                    class="field"
                    placeholder="Only you can see these."
                ></textarea>
                <InputError :message="editForm.errors.notes" />
            </div>
            <div class="flex gap-2">
                <Button type="submit" class="flex-1" :disabled="editForm.processing || !editForm.isDirty">Save changes</Button>
                <Button type="button" variant="outline" @click="confirmOpen = true">Remove</Button>
            </div>
        </form>

        <ConfirmDialog
            v-model:open="confirmOpen"
            title="Remove from library?"
            description="Your status, rating and private notes for this book will be deleted."
            confirm-label="Remove"
            @confirm="remove"
        />
    </section>
</template>
