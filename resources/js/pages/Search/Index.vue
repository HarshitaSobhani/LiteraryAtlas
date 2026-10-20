<script setup lang="ts">
import BookCard from '@/components/BookCard.vue';
import BookCover from '@/components/BookCover.vue';
import EmptyState from '@/components/EmptyState.vue';
import PlaceCard from '@/components/PlaceCard.vue';
import { debounce } from '@/composables/useDebouncedFn';
import SiteLayout from '@/layouts/SiteLayout.vue';
import type { Author, Book, Place } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { ExternalLink, LoaderCircle } from 'lucide-vue-next';
import { ref, watch } from 'vue';

const props = defineProps<{
    q: string;
    results: { books: Book[]; authors: Author[]; locations: Place[] };
    totals: { books: number; authors: number; locations: number };
}>();

const term = ref(props.q);
const loading = ref(false);
const run = debounce(() => {
    router.get(route('search'), term.value ? { q: term.value } : {}, {
        preserveState: true,
        replace: true,
        onStart: () => (loading.value = true),
        onFinish: () => (loading.value = false),
    });
}, 350);
watch(term, run);

// Optional Open Library supplement, loaded on demand and rate limited server-side.
interface OLResult {
    key: string;
    title: string;
    authors: string[];
    year: number | null;
    cover_url: string | null;
    url: string;
}
const ol = ref<{ state: 'idle' | 'loading' | 'ok' | 'error'; results: OLResult[]; total: number; message: string }>({
    state: 'idle',
    results: [],
    total: 0,
    message: '',
});
async function searchOpenLibrary() {
    ol.value = { state: 'loading', results: [], total: 0, message: '' };
    const controller = new AbortController();
    const timer = setTimeout(() => controller.abort(), 10000);
    try {
        const res = await fetch(route('api.openlibrary.search', { q: props.q }), {
            headers: { Accept: 'application/json' },
            signal: controller.signal,
        });
        const body = await res.json().catch(() => ({}));
        if (res.status === 429) throw new Error('Too many searches. Please wait a minute.');
        if (!res.ok)
            throw new Error(
                res.status === 503
                    ? 'Open Library is not responding right now. The local catalog above is unaffected.'
                    : (body.message ?? 'Search failed.'),
            );
        ol.value = { state: 'ok', results: body.results, total: body.total, message: '' };
    } catch (e) {
        ol.value = {
            state: 'error',
            results: [],
            total: 0,
            message: e instanceof DOMException ? 'Open Library took too long to respond.' : (e as Error).message,
        };
    } finally {
        clearTimeout(timer);
    }
}
watch(
    () => props.q,
    () => (ol.value = { state: 'idle', results: [], total: 0, message: '' }),
);

const empty = () => props.q.length >= 2 && !props.totals.books && !props.totals.authors && !props.totals.locations;
</script>

<template>
    <Head title="Search" />
    <SiteLayout>
        <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
            <p class="eyebrow">Search</p>
            <h1 class="mt-1 text-4xl font-semibold">Books, authors and places</h1>
            <form class="mt-6 max-w-2xl" role="search" @submit.prevent="run()">
                <label for="s" class="sr-only">Search books, authors, cities or countries</label>
                <input
                    id="s"
                    v-model="term"
                    type="search"
                    maxlength="100"
                    autofocus
                    class="field h-12 text-base"
                    placeholder="Search by title, author, city or country"
                />
            </form>

            <p class="mt-4 text-sm text-muted-foreground" aria-live="polite">
                <LoaderCircle v-if="loading" class="mr-1 inline size-4 animate-spin" />
                <template v-if="q.length < 2"
                    >Type at least two characters. Text search matches names and places; it is not semantic or AI search.</template
                >
                <template v-else
                    >{{ totals.books }} books · {{ totals.authors }} authors · {{ totals.locations }} places for “{{ q }}” (top 8 of each
                    shown)</template
                >
            </p>

            <EmptyState
                v-if="empty()"
                class="mt-8"
                title="Nothing in the local catalog"
                message="Try another spelling, or look for the book in the wider Open Library catalog below."
            />

            <section v-if="results.books.length" class="mt-10" aria-labelledby="r-books">
                <div class="flex items-end justify-between">
                    <h2 id="r-books" class="text-2xl font-semibold">Books</h2>
                    <Link v-if="totals.books > 8" :href="route('books.index', { q })" class="link-quiet text-sm">All {{ totals.books }} →</Link>
                </div>
                <div class="mt-4 grid grid-cols-2 gap-4 sm:grid-cols-4 lg:grid-cols-8">
                    <BookCard v-for="b in results.books" :key="b.id" :book="b" />
                </div>
            </section>

            <section v-if="results.authors.length" class="mt-10" aria-labelledby="r-authors">
                <h2 id="r-authors" class="text-2xl font-semibold">Authors</h2>
                <ul class="mt-4 flex flex-wrap gap-3">
                    <li v-for="a in results.authors" :key="a.id">
                        <Link :href="route('books.index', { q: a.name })" class="paper-card inline-block px-4 py-2 text-sm hover:border-terracotta"
                            >{{ a.name }}
                            <span class="text-muted-foreground">· {{ a.books_count }} {{ a.books_count === 1 ? 'book' : 'books' }}</span></Link
                        >
                    </li>
                </ul>
            </section>

            <section v-if="results.locations.length" class="mt-10" aria-labelledby="r-locs">
                <h2 id="r-locs" class="text-2xl font-semibold">Places</h2>
                <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4"><PlaceCard v-for="l in results.locations" :key="l.id" :place="l" /></div>
            </section>

            <section v-if="q.length >= 2" class="mt-12 border-t border-border pt-8" aria-labelledby="r-ol">
                <h2 id="r-ol" class="text-2xl font-semibold">Beyond the catalog</h2>
                <p class="mt-1 text-sm text-muted-foreground">
                    Search Open Library for titles not in our curated catalog. These results are not linked to literary places.
                </p>
                <button
                    v-if="ol.state === 'idle'"
                    class="mt-4 rounded-md border border-input bg-card px-4 py-2 text-sm font-medium hover:border-terracotta focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                    @click="searchOpenLibrary"
                >
                    Search Open Library for “{{ q }}”
                </button>
                <p v-if="ol.state === 'loading'" class="mt-4 flex items-center gap-2 text-sm" role="status">
                    <LoaderCircle class="size-4 animate-spin" /> Searching Open Library…
                </p>
                <div v-if="ol.state === 'error'" class="mt-4 rounded-md border border-destructive/40 bg-card p-4 text-sm" role="alert">
                    {{ ol.message }} <button class="link-quiet ml-2 font-medium" @click="searchOpenLibrary">Try again</button>
                </div>
                <template v-if="ol.state === 'ok'">
                    <p class="mt-4 text-sm text-muted-foreground">
                        {{ ol.total.toLocaleString() }} matches on Open Library; showing {{ ol.results.length }}.
                    </p>
                    <p v-if="!ol.results.length" class="mt-2 text-sm">No results.</p>
                    <ul class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <li v-for="r in ol.results" :key="r.key" class="paper-card flex gap-3 p-3">
                            <div class="w-14 shrink-0"><BookCover :title="r.title" :src="r.cover_url" size="sm" /></div>
                            <div class="min-w-0 text-sm">
                                <a
                                    :href="r.url"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="inline-flex items-start gap-1 font-serif font-semibold hover:text-terracotta"
                                    >{{ r.title }} <ExternalLink class="mt-1 size-3 shrink-0" aria-hidden="true" /><span class="sr-only"
                                        >(opens in a new tab)</span
                                    ></a
                                >
                                <p class="text-muted-foreground">{{ r.authors.join(', ') }}</p>
                                <p v-if="r.year" class="text-xs text-muted-foreground">{{ r.year }}</p>
                            </div>
                        </li>
                    </ul>
                </template>
            </section>
        </div>
    </SiteLayout>
</template>
