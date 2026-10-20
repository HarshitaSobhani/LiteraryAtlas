<script setup lang="ts">
import BookCard from '@/components/BookCard.vue';
import BookCover from '@/components/BookCover.vue';
import EmptyState from '@/components/EmptyState.vue';
import PlaceCard from '@/components/PlaceCard.vue';
import { Button } from '@/components/ui/button';
import SiteLayout from '@/layouts/SiteLayout.vue';
import { STATUS_LABELS } from '@/lib/format';
import type { Book, Itinerary, Place, ReadingEntry, ReadingStats, SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/vue3';

defineProps<{
    reading: ReadingEntry[];
    wantToRead: ReadingEntry[];
    recentlyViewed: Book[];
    savedLocations: Place[];
    itineraries: Itinerary[];
    stats: ReadingStats;
}>();
const user = usePage<SharedData>().props.auth.user;
const when = (d: string) => new Date(d).toLocaleDateString(undefined, { dateStyle: 'medium' });
</script>

<template>
    <Head title="Dashboard" />
    <SiteLayout>
        <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
            <p class="eyebrow">Dashboard</p>
            <h1 class="mt-1 text-4xl font-semibold">Welcome back, {{ user.name.split(' ')[0] }}</h1>
            <div class="mt-4 flex flex-wrap gap-3">
                <Button as-child><Link :href="route('library.index', { status: 'reading' })">Continue reading</Link></Button>
                <Button as-child variant="outline"><Link :href="route('locations.index')">Explore the map</Link></Button>
                <Button as-child variant="outline"><Link :href="route('itineraries.index')">Plan a trip</Link></Button>
            </div>

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

            <section class="mt-12" aria-labelledby="reading">
                <h2 id="reading" class="text-2xl font-semibold">Currently reading</h2>
                <ul v-if="reading.length" class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <li v-for="e in reading" :key="e.id" class="paper-card flex gap-4 p-4">
                        <div class="w-16 shrink-0"><BookCover :title="e.book!.title" :src="e.book!.cover_url" size="sm" /></div>
                        <div class="min-w-0 flex-1">
                            <Link :href="route('books.show', e.book!.slug)" class="font-serif font-semibold hover:text-terracotta">{{
                                e.book!.title
                            }}</Link>
                            <p class="text-sm text-muted-foreground">{{ e.book!.authors?.map((a) => a.name).join(', ') }}</p>
                            <div
                                class="mt-3 h-2 overflow-hidden rounded-full bg-muted"
                                role="progressbar"
                                :aria-valuenow="e.progress"
                                aria-valuemin="0"
                                aria-valuemax="100"
                                :aria-label="`${e.progress}% read`"
                            >
                                <div class="h-full bg-primary" :style="{ width: e.progress + '%' }"></div>
                            </div>
                            <p class="mt-1 text-xs text-muted-foreground">{{ e.progress }}% read</p>
                        </div>
                    </li>
                </ul>
                <EmptyState v-else class="mt-4" title="Nothing in progress" message="Start a book from your want-to-read list or browse the catalog."
                    ><Button as-child variant="outline"><Link :href="route('books.index')">Browse books</Link></Button></EmptyState
                >
            </section>

            <section class="mt-12" aria-labelledby="want">
                <h2 id="want" class="text-2xl font-semibold">Want to read</h2>
                <div v-if="wantToRead.length" class="mt-4 grid grid-cols-3 gap-4 sm:grid-cols-4 lg:grid-cols-6">
                    <BookCard v-for="e in wantToRead" :key="e.id" :book="e.book!" />
                </div>
                <EmptyState v-else class="mt-4" title="Your reading list is empty" message="Add books you’d like to read from any book page." />
            </section>

            <section class="mt-12" aria-labelledby="recent">
                <h2 id="recent" class="text-2xl font-semibold">Recently viewed</h2>
                <div v-if="recentlyViewed.length" class="mt-4 grid grid-cols-3 gap-4 sm:grid-cols-4 lg:grid-cols-6">
                    <BookCard v-for="b in recentlyViewed" :key="b.id" :book="b" />
                </div>
                <EmptyState v-else class="mt-4" title="No books viewed yet" message="Books you open will appear here." />
            </section>

            <div class="mt-12 grid gap-10 lg:grid-cols-2">
                <section aria-labelledby="places">
                    <h2 id="places" class="text-2xl font-semibold">Saved destinations</h2>
                    <div v-if="savedLocations.length" class="mt-4 grid gap-4 sm:grid-cols-2">
                        <PlaceCard v-for="p in savedLocations" :key="p.id" :place="p" />
                    </div>
                    <EmptyState v-else class="mt-4" title="No saved places" message="Save places from the map to find them here."
                        ><Button as-child variant="outline"><Link :href="route('locations.index')">Explore the map</Link></Button></EmptyState
                    >
                </section>
                <section aria-labelledby="trips">
                    <h2 id="trips" class="text-2xl font-semibold">Recent itineraries</h2>
                    <ul v-if="itineraries.length" class="mt-4 space-y-3">
                        <li v-for="i in itineraries" :key="i.id" class="paper-card p-4">
                            <Link :href="route('itineraries.show', i.id)" class="font-serif text-lg font-semibold hover:text-terracotta">{{
                                i.name
                            }}</Link>
                            <p class="text-sm text-muted-foreground">
                                {{ i.stops_count }} {{ i.stops_count === 1 ? 'stop' : 'stops' }} · updated {{ when(i.updated_at) }}
                            </p>
                        </li>
                    </ul>
                    <EmptyState v-else class="mt-4" title="No itineraries yet" message="Turn literary places into a trip."
                        ><Button as-child variant="outline"><Link :href="route('itineraries.index')">Plan a trip</Link></Button></EmptyState
                    >
                </section>
            </div>
        </div>
    </SiteLayout>
</template>
