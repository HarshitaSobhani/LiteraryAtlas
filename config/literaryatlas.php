<?php

return [
    'open_library' => [
        'base_url' => env('OPEN_LIBRARY_BASE_URL', 'https://openlibrary.org'),
        'timeout' => (int) env('OPEN_LIBRARY_TIMEOUT', 6),
        'cache_ttl' => (int) env('OPEN_LIBRARY_CACHE_TTL', 3600),
        'user_agent' => env('OPEN_LIBRARY_USER_AGENT', 'LiteraryAtlas/1.0 (+'.env('APP_URL', 'http://localhost').')'),
    ],

    'covers_url' => env('OPEN_LIBRARY_COVERS_URL', 'https://covers.openlibrary.org'),

    'map' => [
        // Default tiles are the OpenStreetMap standard layer. Review
        // https://operations.osmfoundation.org/policies/tiles/ before heavy production use
        // and switch MAP_TILE_URL to a provider you have an account with if needed.
        'tile_url' => env('MAP_TILE_URL', 'https://tile.openstreetmap.org/{z}/{x}/{y}.png'),
        'attribution' => env('MAP_TILE_ATTRIBUTION', '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'),
        'max_zoom' => (int) env('MAP_MAX_ZOOM', 18),
    ],
];
