# Catalog data and sources

- Place facts: summarised in original wording from the Wikipedia article linked in each location's `source_url` (`database/data/locations.json`).
- Coordinates: checked against OpenStreetMap Nominatim at roughly building level (city centres may differ by a few km depending on the chosen centre point). Plaza Hotel (New York) could not be resolved by Nominatim and uses its well-known address coordinates; re-verify if precision matters.
- Books: titles, authors and years cross-checked with Open Library; cover IDs and work keys come from Open Library search. Descriptions are original one-liners.
- Associations are flagged `verified` (named or documented in the work or by the author) or `uncertain` (inspiration, tradition, or author-only links). Review `books.json` before trusting any `uncertain` link as fact.
- Covers load from covers.openlibrary.org in the browser; a designed fallback shows if unavailable.
