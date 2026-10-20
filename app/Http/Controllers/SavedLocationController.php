<?php

namespace App\Http\Controllers;

use App\Models\Location;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SavedLocationController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['location_id' => ['required', 'integer', 'exists:locations,id']]);

        $request->user()->savedLocations()->firstOrCreate(['location_id' => $data['location_id']]);

        return back()->with('status', 'Place saved.');
    }

    public function destroy(Request $request, Location $location): RedirectResponse
    {
        $request->user()->savedLocations()->where('location_id', $location->id)->delete();

        return back()->with('status', 'Place removed from your saved list.');
    }
}
