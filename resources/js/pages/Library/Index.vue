<script setup lang="ts">
import BookCover from '@/components/BookCover.vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import EmptyState from '@/components/EmptyState.vue';
import PlaceCard from '@/components/PlaceCard.vue';
import { Button } from '@/components/ui/button';
import SiteLayout from '@/layouts/SiteLayout.vue';
import { STATUS_LABELS } from '@/lib/format';
import type { Place, ReadingEntry, ReadingStats, ReadingStatus } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { Star } from 'lucide-vue-next';
import { ref } from 'vue';

defineProps<{ entries: ReadingEntry[]; status: string; statuses: Record<string, string>; stats: ReadingStats; savedLocations: Place[] }>();

const toRemove = ref<ReadingEntry | null>(null);
const confirmOpen = ref(false);

function update(entry: ReadingEntry, data: { status?: ReadingStatus; progress?: number }) {
    router.patch(route('library.update', entry.id), data, { preserveScroll: true });
}
function onStatus(entry: ReadingEntry, e: Event) {
    update(entry, { status: (e.target as HTMLSelectElement).value as ReadingStatus });
}
function onProgress(entry: ReadingEntry, e: Event) {
    update(entry, { progress: Number((e.target as HTMLInputElement).value) });
}
function ask(entry: ReadingEntry) {
    toRemove.value = entry;
    confirmOpen.value = true;
}
function remove() {
    if (!toRemove.value) return;
    router.delete(route('library.destroy', toRemove.value.id), { preserveScroll: true, onFinish: () => (confirmOpen.value = false) });
}
</script>

<template>
    <Head title="My library" />
    <SiteLayout>
        <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
            <p class="eyebrow">My library</p>
            <h1 class="mt-1 text-4xl font-semibold">Your reading life</h1>

            <dl class="mt-8 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
                <div v-for="(label, key) in STATUS_LABELS" :key="key" class="paper-card p-4">
                    <dt class="text-xs uppercase tracking-wide text-muted-foreground">{{ label }}</dt>
                    <dd class="font-serif text-3xl font-semibold">{{ stats.by_status[key] }}</dd>
                </div>
                <div class="paper-card p-4">
                    <dt class="text-xs uppercase tracking-wide text-muted-foreground">Finished this year</dt>
                    <dd class="font-serif text-3xl font-semibold">{{ stats.completed_this_year }}</dd>
                </div>
                <div class="paper-card p-4">
                    <dt class="text-xs uppercase tracking-wide text-muted-foreground">Average rating</dt>
                    <dd class="font-serif text-3xl font-semibold">{{ stats.average_rating ?? '—' }}</dd>
                </div>
            </dl>
            <p v-if="stats.genres.length" class="mt-3 text-sm text-muted-foreground">
                Top genres: {{ stats.genres.map((g) => `${g.genre} (${g.count})`).join(', ') }}
            </p>

            <nav class="mt-10 flex flex-wrap gap-2" aria-label="Filter by status">
                <Link
                    :href="route('library.index')"
                    class="rounded-full border px-4 py-1.5 text-sm"
                    :class="!status ? 'border-primary bg-primary text-primary-foreground' : 'border-border bg-card hover:border-terracotta'"
                    :aria-current="!status ? 'page' : undefined"
                    >All ({{ stats.total }})</Link
                >
                <Link
                    v-for="(label, key) in statuses"
                    :key="key"
                    :href="route('library.index', { status: key })"
                    class="rounded-full border px-4 py-1.5 text-sm"
                    :class="status === key ? 'border-primary bg-primary text-primary-foreground' : 'border-border bg-card hover:border-terracotta'"
                    :aria-current="status === key ? 'page' : undefined"
                    >{{ label }} ({{ stats.by_status[key as ReadingStatus] }})</Link
                >
            </nav>

            <ul v-if="entries.length" class="mt-6 space-y-4">
                <li v-for="e in entries" :key="e.id" class="paper-card flex gap-4 p-4">
                    <div class="w-20 shrink-0 sm:w-24"><BookCover :title="e.book!.title" :src="e.book!.cover_url" size="sm" /></div>
                    <div class="min-w-0 flex-1">
                        <Link :href="route('books.show', e.book!.slug)" class="font-serif text-lg font-semibold hover:text-terracotta">{{
                            e.book!.title
                        }}</Link>
                        <p class="text-sm text-muted-foreground">{{ e.book!.authors?.map((a) => a.name).join(', ') }}</p>
                        <div class="mt-3 grid gap-3 sm:grid-cols-2">
                            <div>
                                <label :for="`s-${e.id}`" class="mb-1 block text-xs font-medium">Status</label>
                                <select :id="`s-${e.id}`" :value="e.status" class="field" @change="onStatus(e, $event)">
                                    <option v-for="(label, key) in statuses" :key="key" :value="key">{{ label }}</option>
                                </select>
                            </div>
                            <div v-if="e.status === 'reading' || e.status === 'on_hold'">
                                <label :for="`p-${e.id}`" class="mb-1 block text-xs font-medium">Progress: {{ e.progress }}%</label>
                                <input
                                    :id="`p-${e.id}`"
                                    type="range"
                                    min="0"
                                    max="100"
                                    step="5"
                                    :value="e.progress"
                                    class="w-full accent-[hsl(var(--primary))]"
                                    @change="onProgress(e, $event)"
                                />
                            </div>
                        </div>
                        <p class="mt-3 flex items-center gap-1 text-sm" :aria-label="e.rating ? `Rated ${e.rating} out of 5` : 'Not rated'">
                            <Star
                                v-for="n in 5"
                                :key="n"
                                class="size-4"
                                :class="(e.rating ?? 0) >= n ? 'fill-terracotta text-terracotta' : 'text-muted-foreground/50'"
                                aria-hidden="true"
                            />
                            <span v-if="!e.rating" class="ml-1 text-muted-foreground">Not rated</span>
                        </p>
                        <p v-if="e.notes" class="mt-2 line-clamp-2 text-sm text-muted-foreground">Note: {{ e.notes }}</p>
                        <div class="mt-3 flex gap-3 text-sm">
                            <Link :href="route('books.show', e.book!.slug)" class="link-quiet">Rate &amp; add notes</Link>
                            <button class="link-quiet text-destructive" @click="ask(e)">
                                Remove<span class="sr-only"> {{ e.book!.title }}</span>
                            </button>
                        </div>
                    </div>
                </li>
            </ul>
            <EmptyState
                v-else
                class="mt-6"
                :title="status ? 'Nothing with that status' : 'Your library is empty'"
                message="Find a book you’d like to read and add it from its page."
            >
                <Button as-child><Link :href="route('books.index')">Browse books</Link></Button>
            </EmptyState>

            <section class="mt-16" aria-labelledby="saved">
                <h2 id="saved" class="text-2xl font-semibold">Saved places</h2>
                <div v-if="savedLocations.length" class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <PlaceCard v-for="p in savedLocations" :key="p.id" :place="p" />
                </div>
                <EmptyState v-else class="mt-4" title="No saved places" message="Save places from the map to keep them here.">
                    <Button as-child variant="outline"><Link :href="route('locations.index')">Explore the map</Link></Button>
                </EmptyState>
            </section>

            <ConfirmDialog
                v-model:open="confirmOpen"
                title="Remove this book?"
                :description="`“${toRemove?.book?.title}” and your rating and notes will be deleted from your library.`"
                confirm-label="Remove"
                @confirm="remove"
            />
        </div>
    </SiteLayout>
</template>
