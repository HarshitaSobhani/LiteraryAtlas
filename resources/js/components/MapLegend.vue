<script setup lang="ts">
import { TYPE_COLORS, TYPE_HELP, TYPE_LABELS } from '@/lib/format';
import type { LocationType } from '@/types';

const types = Object.keys(TYPE_LABELS) as LocationType[];
</script>

<template>
    <ul class="flex flex-wrap gap-x-4 gap-y-1 text-xs text-muted-foreground" aria-label="Map legend">
        <li v-for="t in types" :key="t" class="flex items-center gap-1.5" :title="TYPE_HELP[t]">
            <span class="size-2.5 rounded-full" :style="{ background: TYPE_COLORS[t] }" aria-hidden="true"></span>{{ TYPE_LABELS[t] }}
        </li>
    </ul>
</template>
