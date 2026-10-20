# Literary Atlas

Discover books set in real places, explore them on an interactive map, save books and places, track your reading, and plan literary trips.

## Implemented features

- **Landing page** with search, featured books, popular places, collections, how-it-works.
- **Books**: curated catalog (23 books), search by title/author, filter by genre/country/place, sort (title, author, newest, oldest, most places), pagination, detail pages with typed, verified/uncertain place links.
- **Map**: Leaflet + OpenStreetMap tiles, clustering, filter by country/city/category, synchronized list, popups, tile-error handling. 36 places with stored coordinates.
- **Places**: setting vs landmark vs bookstore vs institution; related places; save; add to itinerary.
- **Unified search** (books, authors, places) plus on-demand Open Library search (cached, rate limited, graceful failure). Not AI/semantic search.
- **Library**: want to read / reading / completed / on hold, progress, rating, private notes, statistics from real records, no duplicates, owner-only.
- **Itineraries**: create/rename/delete, add/remove/reorder stops, dates, notes, planned minutes, map, straight-line distances (clearly labelled) and OpenStreetMap directions links.
- **Dashboard**, auth (register, login, logout, password reset, profile settings), toasts, confirmations, empty/loading/error states, 404 page, responsive and keyboard accessible.

## Stack

Laravel 12 (PHP ≥ 8.2, tested on 8.3), Inertia + Vue 3 + TypeScript, Tailwind CSS 3, MySQL (SQLite for tests), Leaflet, Open Library, PHPUnit, Vitest, ESLint, Prettier, Pint.

## Setup

Prerequisites: PHP 8.2+ with `pdo_mysql`, Composer 2, Node 20+ (tested 22), MySQL 8 / MariaDB 10.6+.

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
# create the database and user, then set DB_* in .env
mysql -e "CREATE DATABASE literary_atlas CHARACTER SET utf8mb4; CREATE USER 'literary_atlas'@'localhost' IDENTIFIED BY 'choose-a-password'; GRANT ALL ON literary_atlas.* TO 'literary_atlas'@'localhost';"
php artisan migrate --seed      # seeding is idempotent; re-run `php artisan db:seed` any time
composer dev                    # or: php artisan serve  +  npm run dev
```

Open http://localhost:8000. Mail defaults to the `log` driver (password-reset emails appear in `storage/logs/laravel.log`).

## Configuration

See `.env.example`. Integrations are optional: `OPEN_LIBRARY_*` (book search/covers), `MAP_TILE_URL` / `MAP_TILE_ATTRIBUTION` (map tiles; the default OSM tile server has a [usage policy](https://operations.osmfoundation.org/policies/tiles/) — use a commercial/self-hosted provider for significant traffic), `MAIL_*` (password reset). No secret is used in frontend code.

| Feature | Needs |
|---|---|
| Catalog, map data, library, itineraries | MySQL only |
| Map background tiles | Tile server reachable from browsers |
| Covers, "Search Open Library" | Open Library reachable (falls back gracefully) |
| Password-reset email | SMTP (or log driver locally) |

## Tests and checks

```bash
php artisan test        # PHPUnit, SQLite in-memory, no Vite build required (74 tests)
npm test                # Vitest component/helper tests
npm run types           # vue-tsc
npm run lint:check      # ESLint
vendor/bin/pint --test  # PHP style
npm run build           # production assets
```

Tests never touch your configured database. They also pass against MySQL/MariaDB when `DB_*` env vars are supplied.

## Deployment (PHP host + MySQL)

1. Provision PHP 8.2+ (extensions: mbstring, pdo_mysql, openssl, ctype, xml, curl) and MySQL; point the web root to `public/`.
2. `composer install --no-dev --optimize-autoloader`, `npm ci && npm run build` (or build locally and upload `public/build`).
3. Create `.env` from `.env.example`: `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://…`, `php artisan key:generate`, real `DB_*`, `SESSION_SECURE_COOKIE=true`, real `MAIL_*`, `OPEN_LIBRARY_USER_AGENT` with a contact.
4. `php artisan migrate --force && php artisan db:seed --force`
5. `php artisan config:cache route:cache view:cache`; ensure `storage/` and `bootstrap/cache` are writable.
6. Serve over HTTPS only. Set `CACHE_STORE`/`SESSION_DRIVER` to `database` (default) after migrating.

## Documentation

`docs/API.md` (JSON endpoints), `docs/ARCHITECTURE.md`, `docs/DATA_SOURCES.md` (catalog sources and verification limits).

## Troubleshooting

- *Blank page / "Vite manifest not found"*: run `npm run build` or `npm run dev`.
- *SQLSTATE connection refused*: check `DB_*` and that MySQL is running.
- *Map grey*: tiles blocked or offline; the places list still works.
- *Covers missing*: Open Library unreachable; fallback covers are shown.

## Known limitations

Straight-line distances only (no routing engine). Catalog is small and hand-curated; "uncertain" links are interpretive. Email verification is not enforced. Not deployed anywhere.
