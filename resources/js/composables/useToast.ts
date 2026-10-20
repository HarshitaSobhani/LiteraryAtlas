import { reactive } from 'vue';

export interface Toast {
    id: number;
    kind: 'success' | 'error';
    message: string;
}

const state = reactive<{ toasts: Toast[] }>({ toasts: [] });
let nextId = 1;

export function dismissToast(id: number) {
    state.toasts = state.toasts.filter((t) => t.id !== id);
}

export function pushToast(kind: Toast['kind'], message: string, timeout = 5000) {
    if (state.toasts.some((t) => t.message === message && t.kind === kind)) return;
    const id = nextId++;
    state.toasts.push({ id, kind, message });
    if (timeout > 0) setTimeout(() => dismissToast(id), timeout);
}

export function useToast() {
    return { state, success: (m: string) => pushToast('success', m), error: (m: string) => pushToast('error', m, 8000), dismissToast };
}
