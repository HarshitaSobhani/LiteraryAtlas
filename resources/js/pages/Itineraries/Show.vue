<script setup lang="ts">
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import EmptyState from '@/components/EmptyState.vue';
import InputError from '@/components/InputError.vue';
import LeafletMap, { type MapMarker } from '@/components/LeafletMap.vue';
import { Button } from '@/components/ui/button';
import SiteLayout from '@/layouts/SiteLayout.vue';
import { formatKm, formatMinutes } from '@/lib/format';
import type { Itinerary, ItineraryStop, Place } from '@/types';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ArrowDown, ArrowUp, ExternalLink, Trash2 } from 'lucide-vue-next';
import { computed, reactive, ref } from 'vue';

interface Leg {
    from: string;
    to: string;
    straight_line_km: number;
    directions_url: string;
}
const props = defineProps<{
    itinerary: Itinerary & { stops: ItineraryStop[] };
    legs: (Leg | null)[];
    totalStraightLineKm: number;
    totalMinutes: number | null;
    addable: Pick<Place, 'id' | 'name' | 'city' | 'country'>[];
}>();

const rename = useForm({ name: props.itinerary.name, description: props.itinerary.description ?? '' });
const saveDetails = () => rename.patch(route('itineraries.update', props.itinerary.id), { preserveScroll: true });

const add = useForm({ location_id: '' as number | '' });
const addStop = () => add.post(route('itineraries.stops.store', props.itinerary.id), { preserveScroll: true, onSuccess: () => add.reset() });

const markers = computed<MapMarker[]>(() =>
    props.itinerary.stops.map((s, i) => ({
        id: s.location.id,
        lat: s.location.latitude!,
        lng: s.location.longitude!,
        title: `${i + 1}. ${s.location.name}`,
        subtitle: `${s.location.city}, ${s.location.country}`,
        href: route('locations.show', s.location.slug),
        type: s.location.type,
        label: i + 1,
    })),
);

function move(index: number, delta: number) {
    const ids = props.itinerary.stops.map((s) => s.id);
    const j = index + delta;
    if (j < 0 || j >= ids.length) return;
    [ids[index], ids[j]] = [ids[j], ids[index]];
    router.put(route('itineraries.stops.reorder', props.itinerary.id), { order: ids }, { preserveScroll: true });
}

const edits = reactive<Record<number, { visit_date: string; duration_minutes: number | null; notes: string }>>({});
const errors = ref<Record<number, Record<string, string>>>({});
function draft(s: ItineraryStop) {
    return (edits[s.id] ??= { visit_date: s.visit_date?.slice(0, 10) ?? '', duration_minutes: s.duration_minutes, notes: s.notes ?? '' });
}
function saveStop(s: ItineraryStop) {
    const d = draft(s);
    router.patch(
        route('itineraries.stops.update', [props.itinerary.id, s.id]),
        { visit_date: d.visit_date || null, duration_minutes: d.duration_minutes || null, notes: d.notes || null },
        { preserveScroll: true, onError: (e) => (errors.value[s.id] = e), onSuccess: () => delete errors.value[s.id] },
    );
}

const stopToRemove = ref<ItineraryStop | null>(null);
const removeOpen = ref(false);
const removeStop = () =>
    stopToRemove.value &&
    router.delete(route('itineraries.stops.destroy', [props.itinerary.id, stopToRemove.value.id]), {
        preserveScroll: true,
        onFinish: () => (removeOpen.value = false),
    });
const deleteOpen = ref(false);
const destroy = () => router.delete(route('itineraries.destroy', props.itinerary.id));
</script>

<template>
    <Head :title="itinerary.name" />
    <SiteLayout>
        <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
            <nav aria-label="Breadcrumb" class="mb-4 text-sm text-muted-foreground">
                <Link :href="route('itineraries.index')" class="link-quiet">Itineraries</Link> / {{ itinerary.name }}
            </nav>
            <h1 class="text-4xl font-semibold">{{ itinerary.name }}</h1>
            <p class="mt-2 text-sm text-muted-foreground" aria-live="polite">
                {{ itinerary.stops.length }} {{ itinerary.stops.length === 1 ? 'stop' : 'stops' }}
                <template v-if="itinerary.stops.length > 1">
                    · {{ formatKm(totalStraightLineKm) }} total straight-line distance (not a driving or walking distance)</template
                >
                <template v-if="totalMinutes"> · {{ formatMinutes(totalMinutes) }} planned visiting time</template>
            </p>

            <div class="mt-8 grid gap-8 lg:grid-cols-[1fr_28rem]">
                <div class="space-y-6">
                    <EmptyState
                        v-if="!itinerary.stops.length"
                        title="No stops yet"
                        message="Add a place below, or browse the map and use “Add as a stop” on any place."
                    >
                        <Button as-child variant="outline"><Link :href="route('locations.index')">Browse the map</Link></Button>
                    </EmptyState>

                    <ol class="space-y-0">
                        <template v-for="(s, i) in itinerary.stops" :key="s.id">
                            <li class="paper-card p-4">
                                <div class="flex items-start gap-3">
                                    <span
                                        class="flex size-8 shrink-0 items-center justify-center rounded-full bg-primary font-semibold text-primary-foreground"
                                        aria-hidden="true"
                                        >{{ i + 1 }}</span
                                    >
                                    <div class="min-w-0 flex-1">
                                        <Link
                                            :href="route('locations.show', s.location.slug)"
                                            class="font-serif text-lg font-semibold hover:text-terracotta"
                                            >{{ s.location.name }}</Link
                                        >
                                        <p class="text-sm text-muted-foreground">{{ s.location.city }}, {{ s.location.country }}</p>
                                    </div>
                                    <div class="flex shrink-0">
                                        <Button size="icon" variant="ghost" :disabled="i === 0" @click="move(i, -1)"
                                            ><ArrowUp /><span class="sr-only">Move {{ s.location.name }} up</span></Button
                                        >
                                        <Button size="icon" variant="ghost" :disabled="i === itinerary.stops.length - 1" @click="move(i, 1)"
                                            ><ArrowDown /><span class="sr-only">Move {{ s.location.name }} down</span></Button
                                        >
                                        <Button
                                            size="icon"
                                            variant="ghost"
                                            class="text-destructive"
                                            @click="
                                                stopToRemove = s;
                                                removeOpen = true;
                                            "
                                            ><Trash2 /><span class="sr-only">Remove {{ s.location.name }}</span></Button
                                        >
                                    </div>
                                </div>
                                <form class="mt-3 grid gap-3 sm:grid-cols-3" @submit.prevent="saveStop(s)">
                                    <div>
                                        <label :for="`d-${s.id}`" class="mb-1 block text-xs font-medium">Visit date</label>
                                        <input :id="`d-${s.id}`" v-model="draft(s).visit_date" type="date" class="field" />
                                        <InputError :message="errors[s.id]?.visit_date" />
                                    </div>
                                    <div>
                                        <label :for="`m-${s.id}`" class="mb-1 block text-xs font-medium">Time to spend (minutes)</label>
                                        <input
                                            :id="`m-${s.id}`"
                                            v-model.number="draft(s).duration_minutes"
                                            type="number"
                                            min="1"
                                            max="10080"
                                            class="field"
                                        />
                                        <InputError :message="errors[s.id]?.duration_minutes" />
                                    </div>
                                    <div class="sm:col-span-3">
                                        <label :for="`n-${s.id}`" class="mb-1 block text-xs font-medium">Notes</label>
                                        <textarea
                                            :id="`n-${s.id}`"
                                            v-model="draft(s).notes"
                                            rows="2"
                                            maxlength="2000"
                                            class="field"
                                            placeholder="Opening hours, tickets, what to read before you go…"
                                        ></textarea>
                                        <InputError :message="errors[s.id]?.notes" />
                                    </div>
                                    <div class="sm:col-span-3"><Button type="submit" size="sm" variant="outline">Save stop details</Button></div>
                                </form>
                            </li>
                            <li
                                v-if="legs[i]"
                                class="ml-4 border-l-2 border-dashed border-terracotta/50 py-3 pl-8 text-sm"
                                aria-label="Distance to next stop"
                            >
                                <p>
                                    ≈ {{ formatKm(legs[i]!.straight_line_km) }}
                                    <span class="text-muted-foreground">in a straight line to {{ legs[i]!.to }}</span>
                                </p>
                                <a
                                    :href="legs[i]!.directions_url"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="link-quiet inline-flex items-center gap-1"
                                    >Get directions on OpenStreetMap <ExternalLink class="size-3" aria-hidden="true" /><span class="sr-only"
                                        >(opens in a new tab)</span
                                    ></a
                                >
                            </li>
                        </template>
                    </ol>

                    <form class="paper-card flex flex-wrap items-end gap-3 p-4" @submit.prevent="addStop">
                        <div class="min-w-[14rem] flex-1">
                            <label for="add-place" class="mb-1 block text-sm font-medium">Add a place</label>
                            <select id="add-place" v-model="add.location_id" class="field">
                                <option value="" disabled>Choose a place…</option>
                                <option v-for="a in addable" :key="a.id" :value="a.id">{{ a.name }} — {{ a.city }}</option>
                            </select>
                            <InputError :message="add.errors.location_id" />
                        </div>
                        <Button type="submit" :disabled="!add.location_id || add.processing">Add stop</Button>
                    </form>
                </div>

                <aside class="space-y-6 lg:sticky lg:top-20 lg:self-start">
                    <div class="h-80 lg:h-[26rem]"><LeafletMap :markers="markers" route label="Map of itinerary stops" /></div>
                    <p class="text-xs text-muted-foreground">
                        The dashed line connects stops in order. It is not a route. Use the directions links for road, rail or walking routes.
                    </p>
                    <form class="paper-card space-y-3 p-4" @submit.prevent="saveDetails">
                        <h2 class="font-serif text-lg font-semibold">Details</h2>
                        <div>
                            <label for="rn" class="mb-1 block text-sm font-medium">Name</label
                            ><input id="rn" v-model="rename.name" required maxlength="120" class="field" /><InputError
                                :message="rename.errors.name"
                            />
                        </div>
                        <div>
                            <label for="rd" class="mb-1 block text-sm font-medium">Description</label
                            ><textarea id="rd" v-model="rename.description" rows="3" maxlength="2000" class="field"></textarea
                            ><InputError :message="rename.errors.description" />
                        </div>
                        <div class="flex gap-2">
                            <Button type="submit" size="sm" :disabled="rename.processing || !rename.isDirty">Save</Button
                            ><Button type="button" size="sm" variant="outline" class="text-destructive" @click="deleteOpen = true"
                                >Delete itinerary</Button
                            >
                        </div>
                    </form>
                </aside>
            </div>

            <ConfirmDialog
                v-model:open="removeOpen"
                title="Remove this stop?"
                :description="`“${stopToRemove?.location.name}” will be removed from this itinerary, with its notes.`"
                confirm-label="Remove"
                @confirm="removeStop"
            />
            <ConfirmDialog
                v-model:open="deleteOpen"
                title="Delete itinerary?"
                description="This itinerary and all of its stops will be permanently deleted."
                @confirm="destroy"
            />
        </div>
    </SiteLayout>
</template>
