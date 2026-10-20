<script setup lang="ts">
import type { Paginated } from '@/types';
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{ page: Paginated<unknown> }>();
const links = computed(() => props.page.links);
const label = (l: string) => l.replace('&laquo; Previous', 'Previous').replace('Next &raquo;', 'Next');
</script>

<template>
    <nav v-if="page.last_page > 1" aria-label="Pagination" class="mt-10 flex flex-wrap items-center justify-center gap-1.5">
        <template v-for="(l, i) in links" :key="i">
            <span v-if="!l.url" class="rounded-md px-3 py-1.5 text-sm text-muted-foreground/60" aria-hidden="true">{{ label(l.label) }}</span>
            <Link
                v-else
                :href="l.url"
                preserve-scroll
                :aria-current="l.active ? 'page' : undefined"
                class="rounded-md border px-3 py-1.5 text-sm transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                :class="l.active ? 'border-primary bg-primary text-primary-foreground' : 'border-border bg-card hover:border-terracotta'"
                >{{ label(l.label) }}</Link
            >
        </template>
    </nav>
</template>
