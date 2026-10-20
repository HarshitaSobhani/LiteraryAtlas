<script setup lang="ts">
import { Button } from '@/components/ui/button';
import SiteLayout from '@/layouts/SiteLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{ status: number }>();
const copy = computed(
    () =>
        ({
            403: ['Not for your eyes', 'You do not have permission to open this page.'],
            404: ['This page isn’t on the map', 'We couldn’t find what you were looking for. It may have moved, or never existed.'],
            429: ['Slow down a little', 'Too many requests. Please wait a moment and try again.'],
            503: ['Back shortly', 'The atlas is briefly unavailable. Please try again in a few minutes.'],
        })[props.status] ?? ['Something went wrong', 'An unexpected error occurred. Please try again.'],
);
</script>

<template>
    <Head :title="String(status)" />
    <SiteLayout>
        <div class="mx-auto flex max-w-2xl flex-col items-center px-4 py-24 text-center">
            <p class="font-serif text-8xl font-semibold text-terracotta/70">{{ status }}</p>
            <h1 class="mt-4 text-3xl font-semibold">{{ copy[0] }}</h1>
            <p class="mt-2 text-muted-foreground">{{ copy[1] }}</p>
            <div class="mt-8 flex flex-wrap justify-center gap-3">
                <Button as-child><Link :href="route('home')">Back home</Link></Button>
                <Button as-child variant="outline"><Link :href="route('locations.index')">Open the map</Link></Button>
                <Button as-child variant="outline"><Link :href="route('books.index')">Browse books</Link></Button>
            </div>
        </div>
    </SiteLayout>
</template>
