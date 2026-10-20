import type { PageProps } from '@inertiajs/core';
import type { LucideIcon } from 'lucide-vue-next';

export interface Auth {
    user: User;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavItem {
    title: string;
    href: string;
    icon?: LucideIcon;
    isActive?: boolean;
}

export interface SharedData extends PageProps {
    name: string;
    flash: { status: string | null; error: string | null };
    map: { tileUrl: string; attribution: string; maxZoom: number };
    auth: Auth;
    ziggy: {
        location: string;
        url: string;
        port: null | number;
        defaults: Record<string, unknown>;
        routes: Record<string, string>;
    };
}

export interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
}

export type BreadcrumbItemType = BreadcrumbItem;

export type LocationType = 'story_setting' | 'landmark' | 'bookstore' | 'institution';
export type ReadingStatus = 'want_to_read' | 'reading' | 'completed' | 'on_hold';

export interface Author {
    id: number;
    name: string;
    slug: string;
    books_count?: number;
}

export interface Book {
    id: number;
    title: string;
    slug: string;
    description: string | null;
    first_publish_year: number | null;
    genre: string | null;
    open_library_key: string | null;
    cover_url: string | null;
    locations_count?: number;
    authors?: Author[];
    locations?: Place[];
    pivot?: { relation: string; certainty: 'verified' | 'uncertain'; note: string | null };
}

export interface Place {
    id: number;
    name: string;
    slug: string;
    type: LocationType;
    type_label?: string;
    country: string;
    city: string;
    latitude?: number | null;
    longitude?: number | null;
    summary: string;
    significance?: string;
    source_url?: string | null;
    books_count?: number;
    books?: Book[];
    pivot?: { relation: string; certainty: 'verified' | 'uncertain'; note: string | null };
}

export interface ReadingEntry {
    id: number;
    book_id: number;
    status: ReadingStatus;
    progress: number;
    rating: number | null;
    notes: string | null;
    started_at: string | null;
    finished_at: string | null;
    updated_at: string;
    book?: Book;
}

export interface ItineraryStop {
    id: number;
    position: number;
    location_id: number;
    visit_date: string | null;
    duration_minutes: number | null;
    notes: string | null;
    location: Place;
}

export interface Itinerary {
    id: number;
    name: string;
    description: string | null;
    updated_at: string;
    stops_count?: number;
    stops?: ItineraryStop[];
}

export interface ReadingStats {
    total: number;
    by_status: Record<ReadingStatus, number>;
    completed_this_year: number;
    average_rating: number | null;
    rated_count: number;
    genres: { genre: string; count: number }[];
}

export interface Paginated<T> {
    data: T[];
    current_page: number;
    last_page: number;
    total: number;
    from: number | null;
    to: number | null;
    links: { url: string | null; label: string; active: boolean }[];
}
