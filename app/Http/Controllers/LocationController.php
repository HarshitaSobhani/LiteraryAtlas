<?php

namespace App\Http\Controllers;

use App\Http\Requests\LocationIndexRequest;
use App\Models\Location;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LocationController extends Controller
{
    private const FIELDS = ['id', 'name', 'slug', 'type', 'country', 'city', 'latitude', 'longitude', 'summary'];

    public function index(LocationIndexRequest $request): Response
    {
        return Inertia::render('Locations/Index', [
            'locations' => Location::mappable()->withCount('books')->orderBy('name')->get(self::FIELDS),
            'filters' => [
                'q' => $request->validated('q', ''),
                'country' => $request->validated('country', ''),
                'city' => $request->validated('city', ''),
                'type' => $request->validated('type', ''),
            ],
            'facets' => [
                'countries' => Location::distinct()->orderBy('country')->pluck('country'),
                'cities' => Location::distinct()->orderBy('city')->pluck('city'),
                'types' => Location::TYPES,
            ],
        ]);
    }

    public function show(Request $request, Location $location): Response
    {
        $location->load(['books.authors']);

        $related = Location::where('id', '!=', $location->id)
            ->where(function ($q) use ($location) {
                $q->where('city', $location->city)
                    ->orWhereHas('books', fn ($b) => $b->whereIn('books.id', $location->books->pluck('id')));
            })->orderBy('name')->limit(6)->get(['id', 'name', 'slug', 'type', 'city', 'country', 'summary']);

        $user = $request->user();

        return Inertia::render('Locations/Show', [
            'location' => $location,
            'related' => $related,
            'isSaved' => $user ? $user->savedLocations()->where('location_id', $location->id)->exists() : false,
            'itineraries' => $user ? $user->itineraries()->orderByDesc('updated_at')->get(['id', 'name']) : [],
        ]);
    }
}
