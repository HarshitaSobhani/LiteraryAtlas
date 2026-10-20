import { debounce } from '@/composables/useDebouncedFn';
import { formatKm, formatMinutes, hueFor } from '@/lib/format';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

describe('format helpers', () => {
    it('formats minutes without inventing values', () => {
        expect(formatMinutes(null)).toBe('');
        expect(formatMinutes(90)).toBe('1 h 30 min');
        expect(formatMinutes(45)).toBe('45 min');
    });
    it('formats kilometres', () => expect(formatKm(10.94)).toContain('10.9'));
    it('gives stable hues', () => expect(hueFor('Ulysses')).toBe(hueFor('Ulysses')));
});

describe('debounce', () => {
    beforeEach(() => vi.useFakeTimers());
    afterEach(() => vi.useRealTimers());
    it('only fires the last call', () => {
        const fn = vi.fn();
        const d = debounce(fn, 300);
        d('a');
        d('b');
        vi.advanceTimersByTime(299);
        expect(fn).not.toHaveBeenCalled();
        vi.advanceTimersByTime(1);
        expect(fn).toHaveBeenCalledOnce();
        expect(fn).toHaveBeenCalledWith('b');
    });
});
