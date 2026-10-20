<?php

namespace App\Http\Controllers;

use App\Http\Requests\SearchRequest;
use App\Models\Author;
use App\Models\Book;
use App\Models\Location;
use Inertia\Inertia;
use Inertia\Response;

class SearchController extends Controller
{
    public function __invoke(SearchRequest $request): Response
    {
        $q = trim((string) $request->validated('q', ''));
        $results = ['books' => [], 'authors' => [], 'locations' => []];
        $totals = ['books' => 0, 'authors' => 0, 'locations' => 0];

        if (mb_strlen($q) >= 2) {
            $like = '%'.addcslashes($q, '%_\\').'%';

            $books = Book::search($q);
            $authors = Author::where('name', 'like', $like);
            $locations = Location::search($q);

            $totals = [
                'books' => (clone $books)->count(),
                'authors' => (clone $authors)->count(),
                'locations' => (clone $locations)->count(),
            ];
            $results = [
                'books' => $books->with('authors')->orderBy('title')->limit(8)->get(),
                'authors' => $authors->withCount('books')->orderBy('name')->limit(8)->get(),
                'locations' => $locations->orderBy('name')->limit(8)
                    ->get(['id', 'name', 'slug', 'type', 'country', 'city', 'summary']),
            ];
        }

        return Inertia::render('Search/Index', compact('q', 'results', 'totals'));
    }
}
