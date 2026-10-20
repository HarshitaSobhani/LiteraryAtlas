<script setup lang="ts">
import { dismissToast, pushToast, useToast } from '@/composables/useToast';
import type { SharedData } from '@/types';
import { router, usePage } from '@inertiajs/vue3';
import { CircleAlert, CircleCheck, X } from 'lucide-vue-next';
import { onMounted, onUnmounted, watch } from 'vue';

const { state } = useToast();
const page = usePage<SharedData>();

watch(
    () => page.props.flash,
    (flash) => {
        if (flash?.status) pushToast('success', flash.status);
        if (flash?.error) pushToast('error', flash.error, 8000);
    },
    { immediate: true, deep: true },
);

let off: (() => void) | undefined;
onMounted(() => {
    off = router.on('invalid', (event) => {
        // Non-Inertia responses (e.g. a 429 JSON body) would otherwise open a raw modal.
        const status = event.detail.response.status;
        if (status === 429) {
            event.preventDefault();
            pushToast('error', 'Too many requests. Please wait a moment and try again.');
        }
    });
    router.on('exception', () => pushToast('error', 'Network problem. Check your connection and try again.'));
});
onUnmounted(() => off?.());
</script>

<template>
    <div class="pointer-events-none fixed inset-x-0 bottom-0 z-[2000] flex flex-col items-center gap-2 p-4 sm:items-end" aria-live="polite">
        <TransitionGroup
            enter-active-class="transition duration-200"
            enter-from-class="translate-y-2 opacity-0"
            leave-active-class="transition duration-150"
            leave-to-class="opacity-0"
        >
            <div
                v-for="t in state.toasts"
                :key="t.id"
                class="pointer-events-auto flex w-full max-w-sm items-start gap-3 rounded-lg border bg-card p-3 text-sm shadow-lg"
                :class="t.kind === 'error' ? 'border-destructive/50' : 'border-primary/30'"
                :role="t.kind === 'error' ? 'alert' : 'status'"
            >
                <CircleAlert v-if="t.kind === 'error'" class="mt-0.5 size-4 shrink-0 text-destructive" aria-hidden="true" />
                <CircleCheck v-else class="mt-0.5 size-4 shrink-0 text-primary" aria-hidden="true" />
                <p class="flex-1">{{ t.message }}</p>
                <button
                    class="rounded p-0.5 text-muted-foreground hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                    @click="dismissToast(t.id)"
                >
                    <X class="size-4" /><span class="sr-only">Dismiss</span>
                </button>
            </div>
        </TransitionGroup>
    </div>
</template>
