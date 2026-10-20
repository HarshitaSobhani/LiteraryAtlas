<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';

defineProps<{ title: string; description: string; confirmLabel?: string; busy?: boolean }>();
const open = defineModel<boolean>('open', { required: true });
defineEmits<{ confirm: [] }>();
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>{{ title }}</DialogTitle>
                <DialogDescription>{{ description }}</DialogDescription>
            </DialogHeader>
            <DialogFooter class="gap-2">
                <Button variant="outline" @click="open = false">Cancel</Button>
                <Button variant="destructive" :disabled="busy" @click="$emit('confirm')">{{ confirmLabel ?? 'Delete' }}</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
