<script setup lang="ts">
import SiteLogo from '@/components/SiteLogo.vue';
import ToastHost from '@/components/ToastHost.vue';
import { Button } from '@/components/ui/button';
import type { SharedData } from '@/types';
import { Link, router, usePage } from '@inertiajs/vue3';
import { Menu, Search, X } from 'lucide-vue-next';
import { computed, onMounted, onUnmounted, ref } from 'vue';

const page = usePage<SharedData>();
const user = computed(() => page.props.auth?.user ?? null);
const menuOpen = ref(false);
const navigating = ref(false);

const links = computed(() => [
    { label: 'Books', href: route('books.index'), active: route().current('books.*') },
    { label: 'Map', href: route('locations.index'), active: route().current('locations.*') },
    { label: 'Search', href: route('search'), active: route().current('search') },
    ...(user.value
        ? [
              { label: 'My library', href: route('library.index'), active: route().current('library.*') },
              { label: 'Itineraries', href: route('itineraries.index'), active: route().current('itineraries.*') },
          ]
        : []),
]);

const offs: (() => void)[] = [];
onMounted(() => {
    offs.push(router.on('start', () => (navigating.value = true)));
    offs.push(router.on('finish', () => (navigating.value = false)));
    offs.push(router.on('navigate', () => (menuOpen.value = false)));
});
onUnmounted(() => offs.forEach((o) => o()));

const logout = () => router.post(route('logout'));
</script>

<template>
    <div class="flex min-h-screen flex-col">
        <a
            href="#main"
            class="sr-only z-[3000] rounded bg-primary px-3 py-2 text-primary-foreground focus:not-sr-only focus:fixed focus:left-2 focus:top-2"
            >Skip to content</a
        >

        <header class="sticky top-0 z-[1000] border-b border-border bg-background/90 backdrop-blur">
            <div class="mx-auto flex h-16 max-w-7xl items-center gap-4 px-4 sm:px-6 lg:px-8">
                <Link
                    :href="route('home')"
                    class="flex items-center gap-2 font-serif text-lg font-semibold text-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                >
                    <SiteLogo class="size-7 text-primary" /> Literary Atlas
                </Link>

                <nav class="ml-6 hidden items-center gap-1 md:flex" aria-label="Main">
                    <Link
                        v-for="l in links"
                        :key="l.href"
                        :href="l.href"
                        class="rounded-md px-3 py-2 text-sm font-medium transition-colors hover:bg-secondary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                        :class="l.active ? 'text-terracotta' : 'text-foreground'"
                        :aria-current="l.active ? 'page' : undefined"
                        >{{ l.label }}</Link
                    >
                </nav>

                <div class="ml-auto hidden items-center gap-2 md:flex">
                    <Button as-child variant="ghost" size="icon"
                        ><Link :href="route('search')"><Search /><span class="sr-only">Search</span></Link></Button
                    >
                    <template v-if="user">
                        <Button as-child variant="outline" size="sm"><Link :href="route('dashboard')">Dashboard</Link></Button>
                        <Button as-child variant="ghost" size="sm"
                            ><Link :href="route('profile.edit')">{{ user.name }}</Link></Button
                        >
                        <Button variant="ghost" size="sm" @click="logout">Log out</Button>
                    </template>
                    <template v-else>
                        <Button as-child variant="ghost" size="sm"><Link :href="route('login')">Log in</Link></Button>
                        <Button as-child size="sm"><Link :href="route('register')">Create account</Link></Button>
                    </template>
                </div>

                <button
                    class="ml-auto rounded-md p-2 hover:bg-secondary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring md:hidden"
                    :aria-expanded="menuOpen"
                    aria-controls="mobile-menu"
                    @click="menuOpen = !menuOpen"
                >
                    <X v-if="menuOpen" class="size-5" /><Menu v-else class="size-5" />
                    <span class="sr-only">{{ menuOpen ? 'Close menu' : 'Open menu' }}</span>
                </button>
            </div>

            <div v-show="menuOpen" id="mobile-menu" class="border-t border-border bg-background px-4 py-3 md:hidden">
                <nav class="flex flex-col" aria-label="Mobile">
                    <Link
                        v-for="l in links"
                        :key="l.href"
                        :href="l.href"
                        class="rounded-md px-3 py-2.5 text-base font-medium hover:bg-secondary"
                        :class="l.active ? 'text-terracotta' : ''"
                        >{{ l.label }}</Link
                    >
                    <template v-if="user">
                        <Link :href="route('dashboard')" class="rounded-md px-3 py-2.5 text-base font-medium hover:bg-secondary">Dashboard</Link>
                        <Link :href="route('profile.edit')" class="rounded-md px-3 py-2.5 text-base font-medium hover:bg-secondary"
                            >Account settings</Link
                        >
                        <button class="rounded-md px-3 py-2.5 text-left text-base font-medium hover:bg-secondary" @click="logout">Log out</button>
                    </template>
                    <template v-else>
                        <Link :href="route('login')" class="rounded-md px-3 py-2.5 text-base font-medium hover:bg-secondary">Log in</Link>
                        <Link :href="route('register')" class="rounded-md px-3 py-2.5 text-base font-medium hover:bg-secondary">Create account</Link>
                    </template>
                </nav>
            </div>
            <div v-if="navigating" class="absolute inset-x-0 bottom-0 h-0.5 animate-pulse bg-terracotta" aria-hidden="true"></div>
        </header>

        <main id="main" class="flex-1" tabindex="-1">
            <slot />
        </main>

        <footer class="mt-16 border-t border-border bg-primary text-primary-foreground">
            <div class="mx-auto grid max-w-7xl gap-8 px-4 py-12 sm:px-6 md:grid-cols-4 lg:px-8">
                <div class="md:col-span-2">
                    <p class="flex items-center gap-2 font-serif text-xl font-semibold"><SiteLogo class="size-6" /> Literary Atlas</p>
                    <p class="mt-3 max-w-md text-sm opacity-85">
                        Books, the places that shaped them, and the journeys they inspire. Curated by hand, free to explore.
                    </p>
                </div>
                <nav aria-label="Explore" class="text-sm">
                    <p class="mb-2 font-semibold">Explore</p>
                    <ul class="space-y-1.5 opacity-90">
                        <li><Link :href="route('books.index')" class="hover:underline">Books</Link></li>
                        <li><Link :href="route('locations.index')" class="hover:underline">Literary map</Link></li>
                        <li><Link :href="route('search')" class="hover:underline">Search</Link></li>
                        <li><Link :href="user ? route('itineraries.index') : route('register')" class="hover:underline">Plan a trip</Link></li>
                    </ul>
                </nav>
                <div class="text-sm">
                    <p class="mb-2 font-semibold">Attribution</p>
                    <ul class="space-y-1.5 opacity-90">
                        <li>
                            Map data ©
                            <a class="underline" href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener noreferrer"
                                >OpenStreetMap</a
                            >
                            contributors
                        </li>
                        <li>
                            Covers and metadata from
                            <a class="underline" href="https://openlibrary.org" target="_blank" rel="noopener noreferrer">Open Library</a>
                        </li>
                        <li>
                            Place facts summarised from
                            <a class="underline" href="https://www.wikipedia.org" target="_blank" rel="noopener noreferrer">Wikipedia</a>
                        </li>
                    </ul>
                </div>
            </div>
        </footer>

        <ToastHost />
    </div>
</template>
