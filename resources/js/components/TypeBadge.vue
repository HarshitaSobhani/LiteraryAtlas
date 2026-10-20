<script setup lang="ts">
import { TYPE_COLORS, TYPE_LABELS } from '@/lib/format';
import type { LocationType } from '@/types';

defineProps<{ type: LocationType }>();
</script>

<template>
    <span class="inline-flex items-center gap-1.5 rounded-full border border-border bg-card px-2.5 py-0.5 text-xs font-medium text-foreground">
        <span class="size-2 rounded-full" :style="{ background: TYPE_COLORS[type] }" aria-hidden="true"></span>
        {{ TYPE_LABELS[type] }}
    </span>
</template>
