<script setup lang="ts">
import BookCard from '@/components/BookCard.vue';
import EmptyState from '@/components/EmptyState.vue';
import Pagination from '@/components/Pagination.vue';
import { Button } from '@/components/ui/button';
import { debounce } from '@/composables/useDebouncedFn';
import SiteLayout from '@/layouts/SiteLayout.vue';
import type { Book, Paginated } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { computed, reactive, ref, watch } from 'vue';

const props = defineProps<{
    books: Paginated<Book>;
    filters: { q: string; genre: string; location: string; country: string; sort: string };
    facets: { genres: string[]; locations: { name: string; slug: string }[]; countries: string[] };
}>();

const form = reactive({ ...props.filters });
const loading = ref(false);

function visit() {
    const params = Object.fromEntries(Object.entries(form).filter(([k, v]) => v && !(k === 'sort' && v === 'title')));
    router.get(route('books.index'), params, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        onStart: () => (loading.value = true),
        onFinish: () => (loading.value = false),
    });
}
const debouncedVisit = debounce(visit, 350);
watch(() => form.q, debouncedVisit);
watch(() => [form.genre, form.location, form.country, form.sort], visit);

const hasFilters = computed(() => !!(form.q || form.genre || form.location || form.country));
function reset() {
    Object.assign(form, { q: '', genre: '', location: '', country: '', sort: 'title' });
}
const sorts = [
    { value: 'title', label: 'Title (A–Z)' },
    { value: 'author', label: 'Author (A–Z)' },
    { value: 'newest', label: 'Newest first' },
    { value: 'oldest', label: 'Oldest first' },
    { value: 'places', label: 'Most literary places' },
];
</script>

<template>
    <Head title="Books" />
    <SiteLayout>
        <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
            <p class="eyebrow">Catalog</p>
            <h1 class="mt-1 text-4xl font-semibold">Books and the places behind them</h1>
            <p class="mt-2 max-w-2xl text-muted-foreground">A curated local catalog. Every book here is tied to at least one real place.</p>

            <form
                class="paper-card mt-8 grid gap-4 p-4 sm:grid-cols-2 lg:grid-cols-6"
                role="search"
                aria-label="Filter books"
                @submit.prevent="visit"
            >
                <div class="lg:col-span-2">
                    <label for="q" class="mb-1 block text-sm font-medium">Title or author</label>
                    <input id="q" v-model="form.q" type="search" maxlength="100" class="field" placeholder="e.g. Hemingway" />
                </div>
                <div>
                    <label for="genre" class="mb-1 block text-sm font-medium">Genre</label>
                    <select id="genre" v-model="form.genre" class="field">
                        <option value="">All genres</option>
                        <option v-for="g in facets.genres" :key="g" :value="g">{{ g }}</option>
                    </select>
                </div>
                <div>
                    <label for="country" class="mb-1 block text-sm font-medium">Country</label>
                    <select id="country" v-model="form.country" class="field">
                        <option value="">Any country</option>
                        <option v-for="c in facets.countries" :key="c" :value="c">{{ c }}</option>
                    </select>
                </div>
                <div>
                    <label for="location" class="mb-1 block text-sm font-medium">Place</label>
                    <select id="location" v-model="form.location" class="field">
                        <option value="">Any place</option>
                        <option v-for="l in facets.locations" :key="l.slug" :value="l.slug">{{ l.name }}</option>
                    </select>
                </div>
                <div>
                    <label for="sort" class="mb-1 block text-sm font-medium">Sort by</label>
                    <select id="sort" v-model="form.sort" class="field">
                        <option v-for="s in sorts" :key="s.value" :value="s.value">{{ s.label }}</option>
                    </select>
                </div>
            </form>

            <div class="mt-6 flex flex-wrap items-center justify-between gap-3 text-sm" aria-live="polite">
                <p class="text-muted-foreground">
                    <template v-if="books.total"
                        >Showing {{ books.from }}–{{ books.to }} of {{ books.total }} {{ books.total === 1 ? 'book' : 'books' }}</template
                    >
                    <template v-else>No books match</template>
                </p>
                <Button v-if="hasFilters" variant="ghost" size="sm" @click="reset">Clear filters</Button>
            </div>

            <div v-if="loading" class="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6" aria-busy="true">
                <div v-for="n in 6" :key="n" class="space-y-3 p-2">
                    <div class="aspect-[2/3] animate-pulse rounded bg-muted"></div>
                    <div class="h-4 w-3/4 animate-pulse rounded bg-muted"></div>
                </div>
            </div>
            <div v-else-if="books.data.length" class="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
                <BookCard v-for="b in books.data" :key="b.id" :book="b" />
            </div>
            <EmptyState
                v-else
                class="mt-6"
                title="No books found"
                message="Try a different spelling, remove a filter, or search the wider Open Library catalog."
            >
                <Button v-if="hasFilters" variant="outline" @click="reset">Clear filters</Button>
                <Button as-child><a :href="route('search', { q: form.q })">Search everything</a></Button>
            </EmptyState>

            <Pagination :page="books" />
        </div>
    </SiteLayout>
</template>
