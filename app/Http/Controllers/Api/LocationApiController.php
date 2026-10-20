<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LocationIndexRequest;
use App\Models\Location;
use Illuminate\Http\JsonResponse;

class LocationApiController extends Controller
{
    public function index(LocationIndexRequest $request): JsonResponse
    {
        $f = $request->validated();

        $locations = Location::mappable()
            ->search($f['q'] ?? null)
            ->when($f['country'] ?? null, fn ($q, $v) => $q->where('country', $v))
            ->when($f['city'] ?? null, fn ($q, $v) => $q->where('city', $v))
            ->when($f['type'] ?? null, fn ($q, $v) => $q->where('type', $v))
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'type', 'country', 'city', 'latitude', 'longitude', 'summary']);

        return response()->json(['data' => $locations, 'count' => $locations->count()]);
    }
}
