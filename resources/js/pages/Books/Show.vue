<script setup lang="ts">
import BookCard from '@/components/BookCard.vue';
import BookCover from '@/components/BookCover.vue';
import ReadingPanel from '@/components/ReadingPanel.vue';
import TypeBadge from '@/components/TypeBadge.vue';
import SiteLayout from '@/layouts/SiteLayout.vue';
import { RELATION_LABELS } from '@/lib/format';
import type { Book, ReadingEntry } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { CircleCheck, CircleHelp, ExternalLink } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{ book: Book; entry: ReadingEntry | null; moreByAuthor: Book[]; openLibraryUrl: string | null }>();
const authors = computed(() => (props.book.authors ?? []).map((a) => a.name).join(', '));
</script>

<template>
    <Head :title="book.title" />
    <SiteLayout>
        <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
            <nav aria-label="Breadcrumb" class="mb-6 text-sm text-muted-foreground">
                <Link :href="route('books.index')" class="link-quiet">Books</Link> / {{ book.title }}
            </nav>

            <div class="grid gap-10 lg:grid-cols-[16rem_1fr_20rem]">
                <div class="mx-auto w-full max-w-[14rem] lg:mx-0">
                    <BookCover :title="book.title" :author="authors" :src="book.cover_url" size="lg" />
                </div>

                <div>
                    <p v-if="book.genre" class="eyebrow">{{ book.genre }}</p>
                    <h1 class="mt-1 text-balance text-4xl font-semibold sm:text-5xl">{{ book.title }}</h1>
                    <p class="mt-2 text-lg text-muted-foreground">
                        by {{ authors }}<span v-if="book.first_publish_year"> · first published {{ book.first_publish_year }}</span>
                    </p>
                    <p v-if="book.description" class="mt-6 max-w-prose text-lg leading-relaxed">{{ book.description }}</p>
                    <a
                        v-if="openLibraryUrl"
                        :href="openLibraryUrl"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="link-quiet mt-4 inline-flex items-center gap-1 text-sm"
                    >
                        View on Open Library <ExternalLink class="size-3.5" aria-hidden="true" /><span class="sr-only">(opens in a new tab)</span>
                    </a>

                    <h2 class="mt-10 text-2xl font-semibold">Literary places</h2>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Each link is marked as verified or uncertain, and typed so settings are never confused with landmarks or bookshops.
                    </p>
                    <ul class="mt-4 space-y-3">
                        <li v-for="p in book.locations" :key="p.id" class="paper-card p-4">
                            <div class="flex flex-wrap items-center gap-2">
                                <Link :href="route('locations.show', p.slug)" class="font-serif text-lg font-semibold hover:text-terracotta">{{
                                    p.name
                                }}</Link>
                                <TypeBadge :type="p.type" />
                            </div>
                            <p class="text-sm text-muted-foreground">{{ p.city }}, {{ p.country }}</p>
                            <p class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm">
                                <span class="font-medium">{{ RELATION_LABELS[p.pivot?.relation ?? ''] ?? p.pivot?.relation }}</span>
                                <span v-if="p.pivot?.certainty === 'verified'" class="inline-flex items-center gap-1 text-primary"
                                    ><CircleCheck class="size-4" aria-hidden="true" /> Verified association</span
                                >
                                <span v-else class="inline-flex items-center gap-1 text-terracotta"
                                    ><CircleHelp class="size-4" aria-hidden="true" /> Uncertain / interpretive</span
                                >
                            </p>
                            <p v-if="p.pivot?.note" class="mt-1 text-sm">{{ p.pivot.note }}</p>
                        </li>
                    </ul>
                </div>

                <aside class="space-y-6"><ReadingPanel :book="book" :entry="entry" /></aside>
            </div>

            <section v-if="moreByAuthor.length" class="mt-16" aria-labelledby="more">
                <h2 id="more" class="text-2xl font-semibold">More by this author</h2>
                <div class="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-4 lg:grid-cols-6">
                    <BookCard v-for="b in moreByAuthor" :key="b.id" :book="b" />
                </div>
            </section>
        </div>
    </SiteLayout>
</template>
