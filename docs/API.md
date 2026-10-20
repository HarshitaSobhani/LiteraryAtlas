# Public endpoints

JSON endpoints (no authentication, rate limited per IP). Pages are Inertia routes documented in the README.

## `GET /api/locations`
Verified-coordinate places. Query: `q`, `country`, `city`, `type` (`story_setting|landmark|bookstore|institution`). Limit 60 req/min.
Response `200`: `{"data":[{id,name,slug,type,country,city,latitude,longitude,summary}],"count":n}`. Invalid `type` → `422`.

## `GET /api/openlibrary/search?q=`
Cached (default 1 h) proxy to Open Library search. `q` 2–100 chars (`422` otherwise). Limit 20 req/min (`429`).
`200`: `{"available":true,"total":n,"results":[{key,title,authors[],year,cover_url,url}]}`.
`503`: `{"available":false,"total":0,"results":[]}` when Open Library fails or times out (timeout `OPEN_LIBRARY_TIMEOUT`). Failures are not cached.

Authenticated actions (library, saved places, itineraries) use session auth + CSRF through the web UI and are not a public API.
