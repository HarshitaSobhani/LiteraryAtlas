<script setup lang="ts">
import EmptyState from '@/components/EmptyState.vue';
import LeafletMap, { type MapMarker } from '@/components/LeafletMap.vue';
import MapLegend from '@/components/MapLegend.vue';
import TypeBadge from '@/components/TypeBadge.vue';
import { Button } from '@/components/ui/button';
import SiteLayout from '@/layouts/SiteLayout.vue';
import type { LocationType, Place } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';

const props = defineProps<{
    locations: Place[];
    filters: { q: string; country: string; city: string; type: string };
    facets: { countries: string[]; cities: string[]; types: Record<string, string> };
}>();

const form = reactive({ ...props.filters });
const selectedId = ref<number | null>(null);

const filtered = computed(() => {
    const q = form.q.trim().toLowerCase();
    return props.locations.filter(
        (l) =>
            (!form.country || l.country === form.country) &&
            (!form.city || l.city === form.city) &&
            (!form.type || l.type === form.type) &&
            (!q || [l.name, l.city, l.country].some((s) => s.toLowerCase().includes(q))),
    );
});
const markers = computed<MapMarker[]>(() =>
    filtered.value.map((l) => ({
        id: l.id,
        lat: l.latitude!,
        lng: l.longitude!,
        title: l.name,
        subtitle: `${l.city}, ${l.country}`,
        summary: l.summary,
        href: route('locations.show', l.slug),
        type: l.type as LocationType,
    })),
);
const hasFilters = computed(() => !!(form.q || form.country || form.city || form.type));
const reset = () => Object.assign(form, { q: '', country: '', city: '', type: '' });
</script>

<template>
    <Head title="Literary map" />
    <SiteLayout>
        <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            <p class="eyebrow">Literary map</p>
            <h1 class="mt-1 text-3xl font-semibold sm:text-4xl">Explore the places in the pages</h1>

            <form class="paper-card mt-6 grid gap-3 p-4 sm:grid-cols-2 lg:grid-cols-5" role="search" aria-label="Filter places" @submit.prevent>
                <div class="lg:col-span-2">
                    <label for="map-q" class="mb-1 block text-sm font-medium">Search places</label>
                    <input id="map-q" v-model="form.q" type="search" maxlength="100" class="field" placeholder="Name, city or country" />
                </div>
                <div>
                    <label for="map-country" class="mb-1 block text-sm font-medium">Country</label>
                    <select id="map-country" v-model="form.country" class="field">
                        <option value="">All</option>
                        <option v-for="c in facets.countries" :key="c" :value="c">{{ c }}</option>
                    </select>
                </div>
                <div>
                    <label for="map-city" class="mb-1 block text-sm font-medium">City</label>
                    <select id="map-city" v-model="form.city" class="field">
                        <option value="">All</option>
                        <option v-for="c in facets.cities" :key="c" :value="c">{{ c }}</option>
                    </select>
                </div>
                <div>
                    <label for="map-type" class="mb-1 block text-sm font-medium">Category</label>
                    <select id="map-type" v-model="form.type" class="field">
                        <option value="">All</option>
                        <option v-for="(label, key) in facets.types" :key="key" :value="key">{{ label }}</option>
                    </select>
                </div>
            </form>

            <div class="mt-4 flex flex-wrap items-center justify-between gap-2">
                <MapLegend />
                <p class="text-sm text-muted-foreground" aria-live="polite">
                    {{ filtered.length }} of {{ locations.length }} places
                    <Button v-if="hasFilters" variant="link" size="sm" class="h-auto p-0 pl-2" @click="reset">Clear filters</Button>
                </p>
            </div>

            <div class="mt-4 grid gap-4 lg:grid-cols-[22rem_1fr]">
                <div class="h-[55vh] min-h-[20rem] lg:order-2 lg:h-[70vh]">
                    <LeafletMap :markers="markers" :selected-id="selectedId" cluster @select="selectedId = $event" />
                </div>

                <div class="lg:order-1 lg:max-h-[70vh] lg:overflow-y-auto lg:pr-1 lg:[direction:ltr]">
                    <ul v-if="filtered.length" class="space-y-2" aria-label="Places">
                        <li v-for="l in filtered" :key="l.id">
                            <div
                                class="paper-card flex items-start gap-3 p-3 transition-colors"
                                :class="selectedId === l.id ? 'border-terracotta' : ''"
                            >
                                <button
                                    class="flex-1 text-left focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                                    :aria-pressed="selectedId === l.id"
                                    @click="selectedId = l.id"
                                >
                                    <span class="block font-serif font-semibold">{{ l.name }}</span>
                                    <span class="block text-sm text-muted-foreground">{{ l.city }}, {{ l.country }}</span>
                                    <span class="mt-1 block"><TypeBadge :type="l.type" /></span>
                                </button>
                                <Link :href="route('locations.show', l.slug)" class="link-quiet shrink-0 text-sm"
                                    >Details<span class="sr-only"> for {{ l.name }}</span></Link
                                >
                            </div>
                        </li>
                    </ul>
                    <EmptyState v-else title="No places match" message="Loosen a filter to see more of the map."
                        ><Button variant="outline" @click="reset">Clear filters</Button></EmptyState
                    >
                </div>
            </div>
        </div>
    </SiteLayout>
</template>
