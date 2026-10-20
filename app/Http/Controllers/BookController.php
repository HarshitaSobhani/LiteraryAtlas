<?php

namespace App\Http\Controllers;

use App\Http\Requests\BookIndexRequest;
use App\Models\Book;
use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class BookController extends Controller
{
    public function index(BookIndexRequest $request): Response
    {
        $f = $request->validated();
        $sort = $f['sort'] ?? 'title';

        $query = Book::query()->with('authors')->withCount('locations')
            ->search($f['q'] ?? null)
            ->when($f['genre'] ?? null, fn ($q, $g) => $q->where('genre', $g))
            ->when($f['location'] ?? null, fn ($q, $slug) => $q->whereHas('locations', fn ($l) => $l->where('locations.slug', $slug)))
            ->when($f['country'] ?? null, fn ($q, $c) => $q->whereHas('locations', fn ($l) => $l->where('country', $c)));

        match ($sort) {
            'author' => $query->orderBy(
                DB::table('authors')->join('author_book', 'authors.id', '=', 'author_book.author_id')
                    ->whereColumn('author_book.book_id', 'books.id')->selectRaw('min(authors.name)')
            )->orderBy('title'),
            'newest' => $query->orderByDesc('first_publish_year')->orderBy('title'),
            'oldest' => $query->orderBy('first_publish_year')->orderBy('title'),
            'places' => $query->orderByDesc('locations_count')->orderBy('title'),
            default => $query->orderBy('title'),
        };

        return Inertia::render('Books/Index', [
            'books' => $query->paginate(12)->withQueryString(),
            'filters' => [
                'q' => $f['q'] ?? '',
                'genre' => $f['genre'] ?? '',
                'location' => $f['location'] ?? '',
                'country' => $f['country'] ?? '',
                'sort' => $sort,
            ],
            'facets' => [
                'genres' => Book::whereNotNull('genre')->distinct()->orderBy('genre')->pluck('genre'),
                'locations' => Location::whereHas('books')->orderBy('name')->get(['name', 'slug']),
                'countries' => Location::whereHas('books')->distinct()->orderBy('country')->pluck('country'),
            ],
        ]);
    }

    public function show(Request $request, Book $book): Response
    {
        $book->load(['authors', 'locations']);

        $entry = null;
        if ($user = $request->user()) {
            $user->bookViews()->updateOrCreate(['book_id' => $book->id], ['viewed_at' => now()]);
            $entry = $user->readingEntries()->where('book_id', $book->id)->first();
        }

        $moreByAuthor = Book::whereHas('authors', fn ($a) => $a->whereIn('authors.id', $book->authors->pluck('id')))
            ->where('id', '!=', $book->id)->with('authors')->orderBy('title')->limit(4)->get();

        return Inertia::render('Books/Show', [
            'book' => $book,
            'entry' => $entry,
            'moreByAuthor' => $moreByAuthor,
            'openLibraryUrl' => $book->open_library_key
                ? rtrim(config('literaryatlas.open_library.base_url'), '/').'/works/'.$book->open_library_key
                : null,
        ]);
    }
}
