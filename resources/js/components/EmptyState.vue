<script setup lang="ts">
defineProps<{ title: string; message?: string }>();
</script>

<template>
    <div class="rounded-lg border border-dashed border-input bg-card/60 px-6 py-12 text-center" role="status">
        <p class="font-serif text-xl font-semibold">{{ title }}</p>
        <p v-if="message" class="mx-auto mt-2 max-w-md text-sm text-muted-foreground">{{ message }}</p>
        <div class="mt-5 flex flex-wrap justify-center gap-3"><slot /></div>
    </div>
</template>
