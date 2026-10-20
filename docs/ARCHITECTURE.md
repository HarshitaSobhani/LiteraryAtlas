# Architecture

Modular monolith: Laravel 12 (controllers, form requests, policies, services) rendering Vue 3 + TypeScript pages through Inertia.

- `app/Models` – Book, Author, Location, ReadingEntry, SavedLocation, BookView, Itinerary, ItineraryStop.
- `app/Services` – `OpenLibraryClient` (cached, timeout, fail-soft), `Geo` (haversine + OSM directions link), `ReadingStats`.
- `app/Policies` – owner-only checks for reading entries and itineraries (`Gate::authorize('manage', …)`).
- `database/data/*.json` + `CatalogSeeder` – curated catalog, idempotent (`updateOrCreate` by slug).
- `resources/js/pages` – one component per route; `components/LeafletMap.vue` wraps Leaflet + markercluster.

Schema: authors ↔ books (pivot), books ↔ locations (pivot with `relation`, `certainty`, `note`), reading_entries and saved_locations unique per user, itinerary_stops unique per (itinerary, location) with `position`. User-owned rows cascade on user delete; catalog rows never cascade from user data.

Design decisions: place types (story setting / landmark / bookstore / institution) are distinct from the book–place relation and its verified/uncertain flag. Distances are straight-line only and labelled so; directions are links to openstreetmap.org. No AI/semantic search exists; search is SQL `LIKE` with escaped wildcards.
