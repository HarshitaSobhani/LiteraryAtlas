import BookCover from '@/components/BookCover.vue';
import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

describe('BookCover', () => {
    it('shows a designed fallback when there is no cover', () => {
        const w = mount(BookCover, { props: { title: 'Ulysses', author: 'James Joyce', src: null } });
        expect(w.find('[data-testid="cover-fallback"]').exists()).toBe(true);
        expect(w.find('img').exists()).toBe(false);
        expect(w.text()).toContain('Ulysses');
    });

    it('renders the image when a cover exists', () => {
        const w = mount(BookCover, { props: { title: 'Ulysses', src: 'https://example.test/c.jpg' } });
        expect(w.find('img').attributes('alt')).toBe('Cover of Ulysses');
        expect(w.find('[data-testid="cover-fallback"]').exists()).toBe(false);
    });

    it('falls back when the image fails to load', async () => {
        const w = mount(BookCover, { props: { title: 'Broken', src: 'https://example.test/missing.jpg' } });
        await w.find('img').trigger('error');
        expect(w.find('[data-testid="cover-fallback"]').exists()).toBe(true);
    });
});
