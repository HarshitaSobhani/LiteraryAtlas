<?php

namespace App\Http\Controllers;

use App\Http\Requests\ItineraryRequest;
use App\Models\Itinerary;
use App\Models\Location;
use App\Services\Geo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ItineraryController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Itineraries/Index', [
            'itineraries' => $request->user()->itineraries()->withCount('stops')
                ->orderByDesc('updated_at')->get(),
        ]);
    }

    public function store(ItineraryRequest $request): RedirectResponse
    {
        $itinerary = $request->user()->itineraries()->create($request->validated());

        return to_route('itineraries.show', $itinerary)->with('status', 'Itinerary created.');
    }

    public function show(Itinerary $itinerary): Response
    {
        Gate::authorize('manage', $itinerary);
        $itinerary->load('stops.location');

        $legs = [];
        $total = 0.0;
        $stops = $itinerary->stops->values();
        for ($i = 1; $i < $stops->count(); $i++) {
            $a = $stops[$i - 1]->location;
            $b = $stops[$i]->location;
            if ($a->latitude === null || $b->latitude === null) {
                $legs[] = null;

                continue;
            }
            $km = Geo::haversineKm($a->latitude, $a->longitude, $b->latitude, $b->longitude);
            $total += $km;
            $legs[] = [
                'from' => $a->name,
                'to' => $b->name,
                'straight_line_km' => round($km, 1),
                'directions_url' => Geo::directionsUrl($a->latitude, $a->longitude, $b->latitude, $b->longitude),
            ];
        }

        return Inertia::render('Itineraries/Show', [
            'itinerary' => $itinerary,
            'legs' => $legs,
            'totalStraightLineKm' => round($total, 1),
            'totalMinutes' => $stops->sum('duration_minutes') ?: null,
            'addable' => Location::mappable()->whereNotIn('id', $stops->pluck('location_id'))
                ->orderBy('name')->get(['id', 'name', 'city', 'country']),
        ]);
    }

    public function update(ItineraryRequest $request, Itinerary $itinerary): RedirectResponse
    {
        Gate::authorize('manage', $itinerary);
        $itinerary->update($request->validated());

        return back()->with('status', 'Itinerary updated.');
    }

    public function destroy(Itinerary $itinerary): RedirectResponse
    {
        Gate::authorize('manage', $itinerary);
        $itinerary->delete();

        return to_route('itineraries.index')->with('status', 'Itinerary deleted.');
    }
}
