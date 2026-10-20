<?php

namespace Database\Seeders;

use App\Models\Author;
use App\Models\Book;
use App\Models\Location;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Idempotent seeder for the curated catalog in database/data/*.json.
 * Sources: Wikipedia articles (see locations.json "source_url"); coordinates
 * were cross-checked against OpenStreetMap/Nominatim. Descriptions are original.
 */
class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        $locations = json_decode(file_get_contents(database_path('data/locations.json')), true, 512, JSON_THROW_ON_ERROR);
        $books = json_decode(file_get_contents(database_path('data/books.json')), true, 512, JSON_THROW_ON_ERROR);

        $locationIds = [];
        foreach ($locations as $l) {
            $locationIds[$l['slug']] = Location::updateOrCreate(['slug' => $l['slug']], [
                'name' => $l['name'],
                'type' => $l['type'],
                'country' => $l['country'],
                'city' => $l['city'],
                'latitude' => $l['lat'],
                'longitude' => $l['lon'],
                'coordinates_verified' => true,
                'summary' => $l['summary'],
                'significance' => $l['significance'],
                'source_url' => $l['source'],
            ])->id;
        }

        foreach ($books as $b) {
            $book = Book::updateOrCreate(['slug' => $b['slug']], [
                'title' => $b['title'],
                'description' => $b['description'],
                'first_publish_year' => $b['year'],
                'genre' => $b['genre'],
                'open_library_key' => $b['ol_key'] ?? null,
                'cover_id' => $b['cover_id'] ?? null,
            ]);

            $authorIds = collect($b['authors'])->map(
                fn ($name) => Author::firstOrCreate(['slug' => Str::slug($name)], ['name' => $name])->id
            );
            $book->authors()->syncWithoutDetaching($authorIds->all());

            $sync = [];
            foreach ($b['places'] as [$slug, $relation, $certainty, $note]) {
                $sync[$locationIds[$slug]] = compact('relation', 'certainty', 'note');
            }
            $book->locations()->sync($sync);
        }
    }
}
