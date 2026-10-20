<?php

namespace Tests\Feature;

use App\Models\Author;
use App\Models\Book;
use App\Models\Location;
use Database\Seeders\CatalogSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_renders_without_authentication(): void
    {
        $this->seed(CatalogSeeder::class);

        $this->get('/')->assertOk()->assertInertia(fn (Assert $p) => $p
            ->component('Home')
            ->has('featuredBooks')
            ->where('stats.books', Book::count()));
    }

    public function test_books_index_searches_title_and_author(): void
    {
        $author = Author::factory()->create(['name' => 'Zora Quill']);
        $byAuthor = Book::factory()->create(['title' => 'Unrelated']);
        $byAuthor->authors()->attach($author);
        Book::factory()->create(['title' => 'Moby Quest']);
        Book::factory()->create(['title' => 'Other']);

        $this->get('/books?q=Zora')->assertInertia(fn (Assert $p) => $p
            ->has('books.data', 1)->where('books.data.0.title', 'Unrelated'));

        $this->get('/books?q=Moby')->assertInertia(fn (Assert $p) => $p
            ->has('books.data', 1)->where('books.data.0.title', 'Moby Quest'));
    }

    public function test_search_treats_like_wildcards_literally(): void
    {
        Book::factory()->create(['title' => 'Plain']);

        $this->get('/books?q=%25')->assertInertia(fn (Assert $p) => $p->has('books.data', 0));
    }

    public function test_books_filter_by_genre_and_location_and_sort(): void
    {
        $loc = Location::factory()->create(['slug' => 'paris']);
        $a = Book::factory()->create(['title' => 'B Book', 'genre' => 'Mystery', 'first_publish_year' => 1900]);
        $b = Book::factory()->create(['title' => 'A Book', 'genre' => 'Mystery', 'first_publish_year' => 2000]);
        Book::factory()->create(['title' => 'C Book', 'genre' => 'Fantasy']);
        $a->locations()->attach($loc, ['relation' => 'setting', 'certainty' => 'verified']);
        $b->locations()->attach($loc, ['relation' => 'setting', 'certainty' => 'verified']);

        $this->get('/books?genre=Mystery&sort=newest')->assertInertia(fn (Assert $p) => $p
            ->has('books.data', 2)->where('books.data.0.title', 'A Book'));
        $this->get('/books?location=paris&sort=oldest')->assertInertia(fn (Assert $p) => $p
            ->has('books.data', 2)->where('books.data.0.title', 'B Book'));
        $this->get('/books?sort=title')->assertInertia(fn (Assert $p) => $p
            ->where('books.data.0.title', 'A Book'));
        $this->get('/books?sort=author')->assertOk();
        $this->get('/books?sort=places')->assertOk();
    }

    public function test_books_index_validates_input(): void
    {
        $this->get('/books?sort=bogus')->assertSessionHasErrors('sort');
        $this->get('/books?q='.str_repeat('a', 101))->assertSessionHasErrors('q');
    }

    public function test_books_are_paginated(): void
    {
        Book::factory()->count(15)->create();

        $this->get('/books')->assertInertia(fn (Assert $p) => $p
            ->has('books.data', 12)->where('books.last_page', 2));
    }

    public function test_book_detail_shows_places_with_certainty(): void
    {
        $book = Book::factory()->create();
        $loc = Location::factory()->create();
        $book->locations()->attach($loc, ['relation' => 'inspiration', 'certainty' => 'uncertain', 'note' => 'x']);

        $this->get("/books/{$book->slug}")->assertOk()->assertInertia(fn (Assert $p) => $p
            ->component('Books/Show')
            ->where('book.locations.0.pivot.certainty', 'uncertain'));
    }

    public function test_unknown_book_returns_branded_404(): void
    {
        $this->get('/books/nope')->assertNotFound()
            ->assertInertia(fn (Assert $p) => $p->component('Error')->where('status', 404));
    }

    public function test_map_page_only_exposes_verified_coordinates(): void
    {
        Location::factory()->create(['name' => 'Verified']);
        Location::factory()->unverified()->create(['name' => 'Unverified']);

        $this->get('/map')->assertInertia(fn (Assert $p) => $p
            ->has('locations', 1)->where('locations.0.name', 'Verified'));
    }

    public function test_location_api_filters_and_validates(): void
    {
        Location::factory()->create(['country' => 'France', 'city' => 'Paris', 'type' => 'bookstore']);
        Location::factory()->create(['country' => 'Spain', 'city' => 'Madrid', 'type' => 'landmark']);

        $this->getJson('/api/locations?country=France')->assertOk()->assertJsonPath('count', 1);
        $this->getJson('/api/locations?type=bookstore')->assertOk()->assertJsonPath('count', 1);
        $this->getJson('/api/locations?q=Madrid')->assertOk()->assertJsonPath('count', 1);
        $this->getJson('/api/locations?type=castle')->assertStatus(422);
    }

    public function test_location_detail_lists_books_and_related_places(): void
    {
        $loc = Location::factory()->create(['city' => 'Dublin']);
        $near = Location::factory()->create(['city' => 'Dublin']);
        $book = Book::factory()->create();
        $book->locations()->attach($loc, ['relation' => 'setting', 'certainty' => 'verified']);

        $this->get("/locations/{$loc->slug}")->assertOk()->assertInertia(fn (Assert $p) => $p
            ->component('Locations/Show')
            ->has('location.books', 1)
            ->where('related.0.id', $near->id)
            ->where('isSaved', false));
    }

    public function test_unified_search_returns_books_authors_and_locations(): void
    {
        $this->seed(CatalogSeeder::class);

        $this->get('/search?q=Paris')->assertInertia(fn (Assert $p) => $p
            ->component('Search/Index')
            ->where('totals.locations', fn ($n) => $n >= 3));

        $this->get('/search?q=Joyce')->assertInertia(fn (Assert $p) => $p
            ->where('totals.authors', 1)->where('totals.books', 2));

        $this->get('/search?q=zzzzqqq')->assertInertia(fn (Assert $p) => $p
            ->where('totals.books', 0)->where('totals.locations', 0));
        $this->get('/search?q='.str_repeat('a', 101))->assertSessionHasErrors('q');
    }

    public function test_seeder_is_idempotent_and_coordinates_are_valid(): void
    {
        $this->seed(CatalogSeeder::class);
        $counts = [Book::count(), Location::count(), Author::count(), \DB::table('book_location')->count()];
        $this->seed(CatalogSeeder::class);

        $this->assertSame($counts, [Book::count(), Location::count(), Author::count(), \DB::table('book_location')->count()]);
        $this->assertGreaterThanOrEqual(20, Book::count());
        $this->assertGreaterThanOrEqual(30, Location::count());

        foreach (Location::all() as $l) {
            $this->assertTrue($l->coordinates_verified);
            $this->assertEqualsWithDelta(0, abs($l->latitude) - min(abs($l->latitude), 90), 0.0001, $l->name);
            $this->assertLessThanOrEqual(180, abs($l->longitude), $l->name);
            $this->assertArrayHasKey($l->type, Location::TYPES);
        }
        $this->assertSame(0, Book::whereDoesntHave('locations')->count());
    }

    public function test_slug_uniqueness_is_enforced_by_the_database(): void
    {
        Book::factory()->create(['slug' => 'dup']);

        $this->expectException(QueryException::class);
        Book::factory()->create(['slug' => 'dup']);
    }

    public function test_responses_carry_security_headers(): void
    {
        $this->get('/')->assertHeader('X-Content-Type-Options', 'nosniff')->assertHeader('X-Frame-Options', 'SAMEORIGIN');
    }
}
