<?php

namespace Tests\Feature;

use App\Services\OpenLibraryClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OpenLibraryTest extends TestCase
{
    use RefreshDatabase;

    private function fakeSuccess(): void
    {
        Http::fake(['openlibrary.org/*' => Http::response([
            'numFound' => 2,
            'docs' => [
                ['key' => '/works/OL1W', 'title' => 'Ulysses', 'author_name' => ['James Joyce'], 'first_publish_year' => 1914, 'cover_i' => 123],
                ['key' => '/works/OL2W', 'title' => 'No Cover'],
                ['title' => 'Malformed entry without key'],
            ],
        ])]);
    }

    public function test_search_maps_open_library_response(): void
    {
        $this->fakeSuccess();

        $this->getJson('/api/openlibrary/search?q=ulysses')->assertOk()
            ->assertJsonPath('available', true)
            ->assertJsonCount(2, 'results')
            ->assertJsonPath('results.0.cover_url', 'https://covers.openlibrary.org/b/id/123-M.jpg')
            ->assertJsonPath('results.1.cover_url', null);
    }

    public function test_results_are_cached(): void
    {
        $this->fakeSuccess();

        $this->getJson('/api/openlibrary/search?q=ulysses')->assertOk();
        $this->getJson('/api/openlibrary/search?q=ULYSSES')->assertOk();

        Http::assertSentCount(1);
    }

    public function test_upstream_error_degrades_gracefully(): void
    {
        Http::fake(['openlibrary.org/*' => Http::response('boom', 500)]);

        $this->getJson('/api/openlibrary/search?q=ulysses')->assertStatus(503)
            ->assertJsonPath('available', false)->assertJsonPath('results', []);
    }

    public function test_timeout_degrades_gracefully_and_is_not_cached(): void
    {
        Http::fake(fn () => throw new ConnectionException('timed out'));

        $result = app(OpenLibraryClient::class)->search('anything');

        $this->assertFalse($result['available']);
        $this->assertFalse(Cache::has('openlibrary:search:'.md5('anything|10')));
    }

    public function test_short_or_invalid_queries_are_rejected_without_http_calls(): void
    {
        Http::fake();

        $this->getJson('/api/openlibrary/search?q=a')->assertStatus(422);
        $this->getJson('/api/openlibrary/search?q='.str_repeat('x', 101))->assertStatus(422);
        Http::assertNothingSent();
    }

    public function test_endpoint_is_rate_limited(): void
    {
        $this->fakeSuccess();

        for ($i = 0; $i < 20; $i++) {
            $this->getJson('/api/openlibrary/search?q=book'.$i)->assertOk();
        }
        $this->getJson('/api/openlibrary/search?q=onemore')->assertStatus(429);
    }

    public function test_local_catalog_still_works_when_open_library_is_down(): void
    {
        Http::fake(['*' => Http::response('', 500)]);

        $this->get('/books')->assertOk();
        $this->get('/')->assertOk();
    }
}
