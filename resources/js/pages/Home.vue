<script setup lang="ts">
import BookCard from '@/components/BookCard.vue';
import PlaceCard from '@/components/PlaceCard.vue';
import { Button } from '@/components/ui/button';
import SiteLayout from '@/layouts/SiteLayout.vue';
import type { Book, Place } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { BookMarked, Compass, Map as MapIcon, Route as RouteIcon, Search } from 'lucide-vue-next';
import { ref } from 'vue';

defineProps<{
    featuredBooks: Book[];
    popularPlaces: Place[];
    collections: { title: string; blurb: string; href: string }[];
    stats: { books: number; places: number; countries: number };
}>();

const q = ref('');
const submit = () => router.get(route('search'), { q: q.value });
const suggestions = ['Paris', 'Dublin', 'Austen', 'Hemingway', 'Tokyo'];
const steps = [
    { icon: Search, title: 'Discover', text: 'Search books, authors, cities or countries across a hand-curated catalog.' },
    { icon: MapIcon, title: 'Explore', text: 'See story settings, landmarks, bookshops and writers’ houses on one map.' },
    { icon: BookMarked, title: 'Collect', text: 'Keep a reading list, rate and annotate books, and save places you love.' },
    { icon: RouteIcon, title: 'Travel', text: 'Order literary stops into an itinerary with straight-line distances and map links.' },
];
</script>

<template>
    <Head title="Books, places and journeys" />
    <SiteLayout>
        <section class="relative overflow-hidden border-b border-border bg-primary text-primary-foreground">
            <div
                class="pointer-events-none absolute inset-0 opacity-[0.07]"
                aria-hidden="true"
                style="
                    background-image:
                        repeating-linear-gradient(0deg, currentColor 0 1px, transparent 1px 28px),
                        repeating-linear-gradient(90deg, currentColor 0 1px, transparent 1px 28px);
                "
            ></div>
            <div class="relative mx-auto grid max-w-7xl gap-10 px-4 py-16 sm:px-6 sm:py-24 lg:grid-cols-5 lg:px-8">
                <div class="lg:col-span-3">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-[#e9b8a6]">A field guide for readers who travel</p>
                    <h1 class="mt-4 text-balance text-4xl font-semibold leading-[1.05] sm:text-6xl">Walk into the books you love.</h1>
                    <p class="mt-5 max-w-xl text-lg opacity-90">
                        Find the real streets, houses and bookshops behind great stories, then turn them into a trip.
                    </p>

                    <form class="mt-8 flex max-w-xl flex-col gap-2 sm:flex-row" role="search" @submit.prevent="submit">
                        <label for="hero-search" class="sr-only">Search books, authors, cities or countries</label>
                        <input
                            id="hero-search"
                            v-model="q"
                            type="search"
                            maxlength="100"
                            placeholder="Try “Joyce”, “Paris” or “Japan”"
                            class="field h-12 flex-1 border-transparent bg-background text-base"
                        />
                        <Button type="submit" size="lg" class="h-12 bg-[#a8472f] text-white hover:bg-[#8f3b27]">Search</Button>
                    </form>
                    <p class="mt-3 text-sm opacity-85">
                        Popular:
                        <Link
                            v-for="s in suggestions"
                            :key="s"
                            :href="route('search', { q: s })"
                            class="mr-3 underline decoration-white/40 underline-offset-4 hover:decoration-white"
                            >{{ s }}</Link
                        >
                    </p>
                    <div class="mt-8 flex flex-wrap gap-3">
                        <Button as-child size="lg" variant="secondary"
                            ><Link :href="route('locations.index')"><MapIcon /> Open the literary map</Link></Button
                        >
                        <Button
                            as-child
                            size="lg"
                            variant="outline"
                            class="border-white/40 bg-transparent text-white hover:bg-white/10 hover:text-white"
                            ><Link :href="route('books.index')">Browse books</Link></Button
                        >
                    </div>
                </div>
                <dl class="grid grid-cols-3 gap-4 self-end text-center lg:col-span-2 lg:grid-cols-1 lg:text-left">
                    <div
                        v-for="s in [
                            { n: stats.books, l: 'books catalogued' },
                            { n: stats.places, l: 'literary places' },
                            { n: stats.countries, l: 'countries' },
                        ]"
                        :key="s.l"
                        class="border-t border-white/25 pt-3"
                    >
                        <dt class="order-2 text-xs uppercase tracking-wide opacity-80">{{ s.l }}</dt>
                        <dd class="font-serif text-4xl font-semibold">{{ s.n }}</dd>
                    </div>
                </dl>
            </div>
        </section>

        <section class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8" aria-labelledby="featured-books">
            <div class="flex items-end justify-between gap-4">
                <div>
                    <p class="eyebrow">Featured books</p>
                    <h2 id="featured-books" class="mt-1 text-3xl font-semibold">Stories with an address</h2>
                </div>
                <Link :href="route('books.index')" class="link-quiet text-sm font-medium">All books →</Link>
            </div>
            <div class="mt-8 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
                <BookCard v-for="b in featuredBooks" :key="b.id" :book="b" />
            </div>
        </section>

        <section class="border-y border-border bg-secondary/50" aria-labelledby="popular-places">
            <div class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
                <div class="flex items-end justify-between gap-4">
                    <div>
                        <p class="eyebrow">Popular places</p>
                        <h2 id="popular-places" class="mt-1 text-3xl font-semibold">Where many books meet</h2>
                    </div>
                    <Link :href="route('locations.index')" class="link-quiet text-sm font-medium">See the map →</Link>
                </div>
                <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <PlaceCard v-for="p in popularPlaces" :key="p.id" :place="p" />
                </div>
            </div>
        </section>

        <section class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8" aria-labelledby="collections">
            <p class="eyebrow">Curated collections</p>
            <h2 id="collections" class="mt-1 text-3xl font-semibold">Start somewhere specific</h2>
            <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <Link
                    v-for="c in collections"
                    :key="c.title"
                    :href="c.href"
                    class="paper-card group p-6 transition-all hover:-translate-y-0.5 hover:border-terracotta/60 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                >
                    <Compass class="size-6 text-terracotta" aria-hidden="true" />
                    <h3 class="mt-4 font-serif text-xl font-semibold group-hover:text-terracotta">{{ c.title }}</h3>
                    <p class="mt-1 text-sm text-muted-foreground">{{ c.blurb }}</p>
                </Link>
            </div>
        </section>

        <section class="bg-card" aria-labelledby="how">
            <div class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
                <p class="eyebrow">How it works</p>
                <h2 id="how" class="mt-1 text-3xl font-semibold">From page to pavement in four steps</h2>
                <ol class="mt-10 grid gap-8 sm:grid-cols-2 lg:grid-cols-4">
                    <li v-for="(s, i) in steps" :key="s.title">
                        <span class="font-serif text-5xl font-semibold text-terracotta/70">{{ i + 1 }}</span>
                        <h3 class="mt-2 flex items-center gap-2 text-lg font-semibold">
                            <component :is="s.icon" class="size-5" aria-hidden="true" /> {{ s.title }}
                        </h3>
                        <p class="mt-1 text-sm text-muted-foreground">{{ s.text }}</p>
                    </li>
                </ol>
                <div class="mt-10 flex flex-wrap gap-3">
                    <Button as-child size="lg"><Link :href="route('register')">Create a free account</Link></Button>
                    <Button as-child size="lg" variant="outline"><Link :href="route('locations.index')">Explore without signing up</Link></Button>
                </div>
            </div>
        </section>
    </SiteLayout>
</template>
