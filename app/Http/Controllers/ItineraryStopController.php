<?php

namespace App\Http\Controllers;

use App\Http\Requests\ItineraryStopRequest;
use App\Models\Itinerary;
use App\Models\ItineraryStop;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class ItineraryStopController extends Controller
{
    public function store(ItineraryStopRequest $request, Itinerary $itinerary): RedirectResponse
    {
        Gate::authorize('manage', $itinerary);
        $data = $request->validated();

        if ($itinerary->stops()->where('location_id', $data['location_id'])->exists()) {
            return back()->with('error', 'That place is already in this itinerary.');
        }

        $data['position'] = ($itinerary->stops()->max('position') ?? 0) + 1;
        $itinerary->stops()->create($data);
        $itinerary->touch();

        return back()->with('status', 'Stop added.');
    }

    public function update(ItineraryStopRequest $request, Itinerary $itinerary, ItineraryStop $stop): RedirectResponse
    {
        Gate::authorize('manage', $itinerary);
        abort_unless($stop->itinerary_id === $itinerary->id, 404);

        $stop->update($request->validated());
        $itinerary->touch();

        return back()->with('status', 'Stop updated.');
    }

    public function destroy(Itinerary $itinerary, ItineraryStop $stop): RedirectResponse
    {
        Gate::authorize('manage', $itinerary);
        abort_unless($stop->itinerary_id === $itinerary->id, 404);

        DB::transaction(function () use ($itinerary, $stop) {
            $stop->delete();
            $this->renumber($itinerary, $itinerary->stops()->pluck('id')->all());
        });
        $itinerary->touch();

        return back()->with('status', 'Stop removed.');
    }

    public function reorder(Request $request, Itinerary $itinerary): RedirectResponse
    {
        Gate::authorize('manage', $itinerary);

        $existing = $itinerary->stops()->pluck('id')->all();
        $data = $request->validate([
            'order' => ['required', 'array', 'size:'.count($existing)],
            'order.*' => ['integer', 'distinct', 'in:'.implode(',', $existing ?: [0])],
        ]);

        DB::transaction(fn () => $this->renumber($itinerary, $data['order']));
        $itinerary->touch();

        return back()->with('status', 'Order saved.');
    }

    /** @param list<int> $orderedIds */
    private function renumber(Itinerary $itinerary, array $orderedIds): void
    {
        foreach (array_values($orderedIds) as $i => $id) {
            ItineraryStop::where('itinerary_id', $itinerary->id)->where('id', $id)->update(['position' => $i + 1]);
        }
    }
}
