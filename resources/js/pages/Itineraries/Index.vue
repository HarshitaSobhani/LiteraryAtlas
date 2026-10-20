<script setup lang="ts">
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import EmptyState from '@/components/EmptyState.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import SiteLayout from '@/layouts/SiteLayout.vue';
import type { Itinerary } from '@/types';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps<{ itineraries: Itinerary[] }>();

const form = useForm({ name: '', description: '' });
const create = () => form.post(route('itineraries.store'), { onSuccess: () => form.reset() });

const target = ref<Itinerary | null>(null);
const open = ref(false);
const ask = (i: Itinerary) => {
    target.value = i;
    open.value = true;
};
const destroy = () => target.value && router.delete(route('itineraries.destroy', target.value.id), { onFinish: () => (open.value = false) });
const when = (d: string) => new Date(d).toLocaleDateString(undefined, { dateStyle: 'medium' });
</script>

<template>
    <Head title="Itineraries" />
    <SiteLayout>
        <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
            <p class="eyebrow">Travel planner</p>
            <h1 class="mt-1 text-4xl font-semibold">Your literary itineraries</h1>

            <div class="mt-8 grid gap-8 lg:grid-cols-[1fr_22rem]">
                <div>
                    <ul v-if="itineraries.length" class="space-y-3">
                        <li v-for="i in itineraries" :key="i.id" class="paper-card flex items-center justify-between gap-4 p-4">
                            <div class="min-w-0">
                                <Link :href="route('itineraries.show', i.id)" class="font-serif text-xl font-semibold hover:text-terracotta">{{
                                    i.name
                                }}</Link>
                                <p class="text-sm text-muted-foreground">
                                    {{ i.stops_count }} {{ i.stops_count === 1 ? 'stop' : 'stops' }} · updated {{ when(i.updated_at) }}
                                </p>
                                <p v-if="i.description" class="mt-1 line-clamp-1 text-sm">{{ i.description }}</p>
                            </div>
                            <div class="flex shrink-0 gap-2">
                                <Button as-child size="sm" variant="outline"><Link :href="route('itineraries.show', i.id)">Open</Link></Button>
                                <Button size="sm" variant="ghost" class="text-destructive" @click="ask(i)"
                                    >Delete<span class="sr-only"> {{ i.name }}</span></Button
                                >
                            </div>
                        </li>
                    </ul>
                    <EmptyState v-else title="No itineraries yet" message="Create one, then add places from the map or any place page.">
                        <Button as-child variant="outline"><Link :href="route('locations.index')">Explore the map</Link></Button>
                    </EmptyState>
                </div>

                <form class="paper-card h-fit space-y-4 p-5" @submit.prevent="create">
                    <h2 class="font-serif text-lg font-semibold">New itinerary</h2>
                    <div>
                        <label for="name" class="mb-1 block text-sm font-medium">Name</label>
                        <input id="name" v-model="form.name" required maxlength="120" class="field" placeholder="e.g. Paris with Hemingway" />
                        <InputError :message="form.errors.name" />
                    </div>
                    <div>
                        <label for="desc" class="mb-1 block text-sm font-medium"
                            >Description <span class="font-normal text-muted-foreground">(optional)</span></label
                        >
                        <textarea id="desc" v-model="form.description" rows="3" maxlength="2000" class="field"></textarea>
                        <InputError :message="form.errors.description" />
                    </div>
                    <Button type="submit" class="w-full" :disabled="form.processing">Create itinerary</Button>
                </form>
            </div>

            <ConfirmDialog
                v-model:open="open"
                title="Delete itinerary?"
                :description="`“${target?.name}” and all of its stops will be permanently deleted.`"
                @confirm="destroy"
            />
        </div>
    </SiteLayout>
</template>
