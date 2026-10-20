<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Thin, cached, fail-soft wrapper around the Open Library search endpoint.
 * Never throws: callers receive an `available` flag instead.
 */
class OpenLibraryClient
{
    /**
     * @return array{available: bool, total: int, results: list<array<string, mixed>>}
     */
    public function search(string $term, int $limit = 10): array
    {
        $term = trim($term);
        $key = 'openlibrary:search:'.md5(mb_strtolower($term).'|'.$limit);
        $ttl = (int) config('literaryatlas.open_library.cache_ttl');

        $cached = Cache::get($key);
        if ($cached !== null) {
            return $cached;
        }

        try {
            $response = Http::baseUrl(config('literaryatlas.open_library.base_url'))
                ->withUserAgent(config('literaryatlas.open_library.user_agent'))
                ->timeout(config('literaryatlas.open_library.timeout'))
                ->acceptJson()
                ->get('/search.json', [
                    'q' => $term,
                    'limit' => $limit,
                    'fields' => 'key,title,author_name,first_publish_year,cover_i',
                ]);

            if (! $response->successful()) {
                throw new \RuntimeException('Open Library returned HTTP '.$response->status());
            }

            $payload = [
                'available' => true,
                'total' => (int) $response->json('numFound', 0),
                'results' => collect($response->json('docs', []))
                    ->filter(fn ($d) => isset($d['key'], $d['title']))
                    ->map(fn ($d) => [
                        'key' => $d['key'],
                        'title' => $d['title'],
                        'authors' => array_slice($d['author_name'] ?? [], 0, 3),
                        'year' => $d['first_publish_year'] ?? null,
                        'cover_url' => isset($d['cover_i'])
                            ? rtrim(config('literaryatlas.covers_url'), '/')."/b/id/{$d['cover_i']}-M.jpg"
                            : null,
                        'url' => rtrim(config('literaryatlas.open_library.base_url'), '/').$d['key'],
                    ])->values()->all(),
            ];

            Cache::put($key, $payload, $ttl);

            return $payload;
        } catch (ConnectionException|Throwable $e) {
            Log::warning('Open Library lookup failed', ['error' => $e->getMessage()]);

            return ['available' => false, 'total' => 0, 'results' => []];
        }
    }
}
