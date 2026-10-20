<script setup lang="ts">
import TypeBadge from '@/components/TypeBadge.vue';
import type { Place } from '@/types';
import { Link } from '@inertiajs/vue3';

defineProps<{ place: Place }>();
</script>

<template>
    <Link
        :href="route('locations.show', place.slug)"
        class="paper-card group block h-full p-5 transition-all duration-200 hover:-translate-y-0.5 hover:border-terracotta/60 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
    >
        <TypeBadge :type="place.type" />
        <h3 class="mt-3 font-serif text-lg font-semibold leading-snug group-hover:text-terracotta">{{ place.name }}</h3>
        <p class="text-sm text-muted-foreground">{{ place.city }}, {{ place.country }}</p>
        <p class="mt-2 line-clamp-3 text-sm">{{ place.summary }}</p>
    </Link>
</template>
