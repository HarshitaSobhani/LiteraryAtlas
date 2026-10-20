<script setup lang="ts">
import SiteLayout from '@/layouts/SiteLayout.vue';
import type { BreadcrumbItemType } from '@/types';

withDefaults(defineProps<{ breadcrumbs?: BreadcrumbItemType[] }>(), { breadcrumbs: () => [] });
</script>

<template>
    <SiteLayout>
        <div class="mx-auto max-w-5xl px-4 py-10 sm:px-6 lg:px-8"><slot /></div>
    </SiteLayout>
</template>
