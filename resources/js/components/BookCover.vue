<script setup lang="ts">
import { hueFor } from '@/lib/format';
import { computed, ref, watch } from 'vue';

const props = withDefaults(defineProps<{ title: string; author?: string; src?: string | null; size?: 'sm' | 'md' | 'lg' }>(), { size: 'md' });

const failed = ref(false);
const loaded = ref(false);
watch(
    () => props.src,
    () => {
        failed.value = false;
        loaded.value = false;
    },
);

const showImage = computed(() => !!props.src && !failed.value);
const hue = computed(() => hueFor(props.title));
</script>

<template>
    <div
        class="relative aspect-[2/3] w-full overflow-hidden rounded-[3px] border border-black/10 bg-secondary shadow-[2px_3px_0_rgb(0_0_0/0.08),0_14px_24px_-14px_rgb(0_0_0/0.45)]"
        :class="{ 'max-w-[9rem]': size === 'sm' }"
        data-testid="book-cover"
    >
        <img
            v-if="showImage"
            :src="src!"
            :alt="`Cover of ${title}`"
            loading="lazy"
            class="h-full w-full object-cover transition-opacity duration-300"
            :class="loaded ? 'opacity-100' : 'opacity-0'"
            @load="loaded = true"
            @error="failed = true"
        />
        <div
            v-if="!showImage"
            class="absolute inset-0 flex flex-col justify-between p-3 text-center"
            :style="{ background: `linear-gradient(160deg, hsl(${hue} 32% 28%), hsl(${(hue + 40) % 360} 36% 18%))`, color: '#f7f3ea' }"
            data-testid="cover-fallback"
            role="img"
            :aria-label="`${title}${author ? ' by ' + author : ''} (no cover available)`"
        >
            <div class="mx-auto mt-1 h-px w-8 bg-[#f7f3ea]/50"></div>
            <div>
                <p class="text-balance font-serif font-semibold leading-tight" :class="size === 'lg' ? 'text-xl' : 'text-sm'">{{ title }}</p>
                <p v-if="author" class="mt-2 text-[0.65rem] uppercase tracking-widest opacity-80">{{ author }}</p>
            </div>
            <div class="mx-auto mb-1 h-px w-8 bg-[#f7f3ea]/50"></div>
        </div>
    </div>
</template>
