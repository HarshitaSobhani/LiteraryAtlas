<script setup lang="ts">
import BookCover from '@/components/BookCover.vue';
import LeafletMap from '@/components/LeafletMap.vue';
import PlaceCard from '@/components/PlaceCard.vue';
import TypeBadge from '@/components/TypeBadge.vue';
import { Button } from '@/components/ui/button';
import SiteLayout from '@/layouts/SiteLayout.vue';
import { RELATION_LABELS, TYPE_HELP } from '@/lib/format';
import type { Itinerary, Place, SharedData } from '@/types';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { Bookmark, BookmarkCheck, CircleCheck, CircleHelp, ExternalLink, Route } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{ location: Place; related: Place[]; isSaved: boolean; itineraries: Pick<Itinerary, 'id' | 'name'>[] }>();
const page = usePage<SharedData>();
const user = computed(() => page.props.auth.user);

const markers = computed(() => [
    {
        id: props.location.id,
        lat: props.location.latitude!,
        lng: props.location.longitude!,
        title: props.location.name,
        subtitle: `${props.location.city}, ${props.location.country}`,
        type: props.location.type,
    },
]);

const toggleSave = () => {
    if (props.isSaved) router.delete(route('saved-locations.destroy', props.location.slug), { preserveScroll: true });
    else router.post(route('saved-locations.store'), { location_id: props.location.id }, { preserveScroll: true });
};

const addForm = useForm({ itinerary_id: props.itineraries[0]?.id ?? (null as number | null) });
const addToItinerary = () => {
    if (!addForm.itinerary_id) return;
    router.post(route('itineraries.stops.store', addForm.itinerary_id), { location_id: props.location.id }, { preserveScroll: true });
};
</script>

<template>
    <Head :title="location.name" />
    <SiteLayout>
        <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
            <nav aria-label="Breadcrumb" class="mb-6 text-sm text-muted-foreground">
                <Link :href="route('locations.index')" class="link-quiet">Map</Link> / {{ location.name }}
            </nav>

            <div class="grid gap-10 lg:grid-cols-[1fr_22rem]">
                <div>
                    <TypeBadge :type="location.type" />
                    <h1 class="mt-3 text-balance text-4xl font-semibold sm:text-5xl">{{ location.name }}</h1>
                    <p class="mt-1 text-lg text-muted-foreground">{{ location.city }}, {{ location.country }}</p>
                    <p class="mt-1 text-xs text-muted-foreground">{{ TYPE_HELP[location.type] }}</p>

                    <p class="mt-6 max-w-prose text-lg leading-relaxed">{{ location.summary }}</p>
                    <h2 class="mt-8 text-2xl font-semibold">Literary significance</h2>
                    <p class="mt-2 max-w-prose leading-relaxed">{{ location.significance }}</p>
                    <p class="mt-3 text-sm text-muted-foreground">
                        Coordinates {{ location.latitude?.toFixed(4) }}, {{ location.longitude?.toFixed(4) }} (verified against OpenStreetMap).
                        <a
                            v-if="location.source_url"
                            :href="location.source_url"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="link-quiet inline-flex items-center gap-1"
                            >Source <ExternalLink class="size-3" aria-hidden="true" /><span class="sr-only">(opens in a new tab)</span></a
                        >
                    </p>

                    <h2 class="mt-10 text-2xl font-semibold">Books and authors</h2>
                    <p v-if="!location.books?.length" class="mt-2 text-sm text-muted-foreground">No catalogued book is attached to this place yet.</p>
                    <ul class="mt-4 space-y-4">
                        <li v-for="b in location.books" :key="b.id" class="paper-card flex gap-4 p-4">
                            <div class="w-16 shrink-0"><BookCover :title="b.title" :src="b.cover_url" size="sm" /></div>
                            <div>
                                <Link :href="route('books.show', b.slug)" class="font-serif text-lg font-semibold hover:text-terracotta">{{
                                    b.title
                                }}</Link>
                                <p class="text-sm text-muted-foreground">{{ b.authors?.map((a) => a.name).join(', ') }}</p>
                                <p class="mt-1 flex flex-wrap items-center gap-x-3 text-sm">
                                    <span class="font-medium">{{ RELATION_LABELS[b.pivot?.relation ?? ''] ?? b.pivot?.relation }}</span>
                                    <span v-if="b.pivot?.certainty === 'verified'" class="inline-flex items-center gap-1 text-primary"
                                        ><CircleCheck class="size-4" aria-hidden="true" /> Verified</span
                                    >
                                    <span v-else class="inline-flex items-center gap-1 text-terracotta"
                                        ><CircleHelp class="size-4" aria-hidden="true" /> Uncertain</span
                                    >
                                </p>
                                <p v-if="b.pivot?.note" class="mt-1 text-sm">{{ b.pivot.note }}</p>
                            </div>
                        </li>
                    </ul>
                </div>

                <aside class="space-y-6">
                    <div class="h-64"><LeafletMap :markers="markers" :label="`Map showing ${location.name}`" /></div>

                    <section class="paper-card space-y-4 p-5" aria-label="Your actions">
                        <template v-if="user">
                            <Button class="w-full" :variant="isSaved ? 'secondary' : 'default'" @click="toggleSave">
                                <BookmarkCheck v-if="isSaved" /><Bookmark v-else />{{ isSaved ? 'Saved — remove' : 'Save this place' }}
                            </Button>
                            <form v-if="itineraries.length" class="space-y-2" @submit.prevent="addToItinerary">
                                <label for="itin" class="block text-sm font-medium">Add to itinerary</label>
                                <select id="itin" v-model="addForm.itinerary_id" class="field">
                                    <option v-for="i in itineraries" :key="i.id" :value="i.id">{{ i.name }}</option>
                                </select>
                                <Button type="submit" variant="outline" class="w-full"><Route /> Add as a stop</Button>
                            </form>
                            <p v-else class="text-sm text-muted-foreground">
                                You have no itineraries yet. <Link :href="route('itineraries.index')" class="link-quiet">Create one</Link> to add this
                                place.
                            </p>
                        </template>
                        <template v-else>
                            <p class="text-sm text-muted-foreground">Sign in to save this place or add it to a travel itinerary.</p>
                            <div class="flex gap-2">
                                <Button as-child size="sm"><Link :href="route('login')">Log in</Link></Button
                                ><Button as-child size="sm" variant="outline"><Link :href="route('register')">Sign up</Link></Button>
                            </div>
                        </template>
                    </section>
                </aside>
            </div>

            <section v-if="related.length" class="mt-16" aria-labelledby="related">
                <h2 id="related" class="text-2xl font-semibold">Related places</h2>
                <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3"><PlaceCard v-for="p in related" :key="p.id" :place="p" /></div>
            </section>
        </div>
    </SiteLayout>
</template>
