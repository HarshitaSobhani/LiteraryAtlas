import type { LocationType, ReadingStatus } from '@/types';

export const TYPE_LABELS: Record<LocationType, string> = {
    story_setting: 'Story setting',
    landmark: 'Landmark',
    bookstore: 'Bookstore',
    institution: 'Literary institution',
};

export const TYPE_COLORS: Record<LocationType, string> = {
    story_setting: '#1d3f31',
    landmark: '#a8472f',
    bookstore: '#7a5c12',
    institution: '#38566a',
};

export const TYPE_HELP: Record<LocationType, string> = {
    story_setting: 'A real place where the story is set',
    landmark: 'A real-world site associated with a book',
    bookstore: 'An independent or notable bookshop',
    institution: 'A museum, library, house or literary organisation',
};

export const STATUS_LABELS: Record<ReadingStatus, string> = {
    want_to_read: 'Want to read',
    reading: 'Reading',
    completed: 'Completed',
    on_hold: 'On hold',
};

export const RELATION_LABELS: Record<string, string> = {
    setting: 'Story setting',
    landmark: 'Landmark in the story',
    inspiration: 'Inspiration',
    author_connection: 'Author connection',
    publisher_link: 'Publishing link',
};

export function formatMinutes(total: number | null | undefined): string {
    if (!total) return '';
    const h = Math.floor(total / 60);
    const m = total % 60;
    return [h ? `${h} h` : '', m ? `${m} min` : ''].filter(Boolean).join(' ');
}

export function formatKm(km: number): string {
    return `${km.toLocaleString(undefined, { maximumFractionDigits: 1 })} km`;
}

/** Deterministic hue so each missing cover gets a stable, designed fallback. */
export function hueFor(text: string): number {
    let h = 0;
    for (const ch of text) h = (h * 31 + ch.charCodeAt(0)) % 360;
    return h;
}
