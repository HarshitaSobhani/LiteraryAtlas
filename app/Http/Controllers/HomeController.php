<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Location;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __invoke(): Response
    {
        $featuredSlugs = ['ulysses', 'a-moveable-feast', 'persuasion', 'the-shadow-of-the-wind', 'crime-and-punishment', 'norwegian-wood'];

        $books = Book::with('authors')->withCount('locations')
            ->whereIn('slug', $featuredSlugs)->get()
            ->sortBy(fn ($b) => array_search($b->slug, $featuredSlugs))->values();

        $cities = Location::mappable()->where('type', 'story_setting')->orderBy('name')->get(['id', 'name', 'slug', 'country', 'city', 'summary', 'type']);

        // Cities that appear in the most catalog entries become "popular places".
        $popular = Location::mappable()->withCount('books')->orderByDesc('books_count')->orderBy('name')->limit(6)
            ->get(['id', 'name', 'slug', 'country', 'city', 'summary', 'type']);

        $collections = [
            ['title' => 'Paris, a movable feast', 'blurb' => 'Cafés, bookshops and the streets of two classics.', 'href' => route('locations.index', ['city' => 'Paris'])],
            ['title' => 'Austen\'s England', 'blurb' => 'Chawton, Bath and the Cobb at Lyme Regis.', 'href' => route('books.index', ['q' => 'Austen'])],
            ['title' => 'Bookshop pilgrimages', 'blurb' => 'Independent shops worth a detour.', 'href' => route('locations.index', ['type' => 'bookstore'])],
            ['title' => 'Mystery & the moors', 'blurb' => 'Baker Street and Dartmoor with Sherlock Holmes.', 'href' => route('books.index', ['genre' => 'Mystery'])],
        ];

        return Inertia::render('Home', [
            'featuredBooks' => $books,
            'popularPlaces' => $popular,
            'collections' => $collections,
            'stats' => [
                'books' => Book::count(),
                'places' => Location::count(),
                'countries' => Location::distinct('country')->count('country'),
            ],
            'cityCount' => $cities->count(),
        ]);
    }
}
