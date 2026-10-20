<script setup lang="ts">
import BookCover from '@/components/BookCover.vue';
import type { Book } from '@/types';
import { Link } from '@inertiajs/vue3';
import { MapPin } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{ book: Book }>();
const authors = computed(() => (props.book.authors ?? []).map((a) => a.name).join(', '));
</script>

<template>
    <Link
        :href="route('books.show', book.slug)"
        class="group flex flex-col gap-3 rounded-lg p-2 transition-transform duration-200 hover:-translate-y-0.5 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
    >
        <BookCover :title="book.title" :author="authors" :src="book.cover_url" />
        <div class="px-1">
            <h3 class="font-serif text-base font-semibold leading-snug group-hover:text-terracotta">{{ book.title }}</h3>
            <p class="text-sm text-muted-foreground">{{ authors }}</p>
            <p class="mt-1 flex items-center gap-3 text-xs text-muted-foreground">
                <span v-if="book.first_publish_year">{{ book.first_publish_year }}</span>
                <span v-if="book.locations_count !== undefined" class="inline-flex items-center gap-1">
                    <MapPin class="size-3" aria-hidden="true" />{{ book.locations_count }} {{ book.locations_count === 1 ? 'place' : 'places' }}
                </span>
            </p>
        </div>
    </Link>
</template>
