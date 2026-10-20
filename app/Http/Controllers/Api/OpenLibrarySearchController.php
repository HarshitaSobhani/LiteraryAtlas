<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SearchRequest;
use App\Services\OpenLibraryClient;
use Illuminate\Http\JsonResponse;

class OpenLibrarySearchController extends Controller
{
    public function __invoke(SearchRequest $request, OpenLibraryClient $client): JsonResponse
    {
        $term = trim((string) $request->validated('q'));
        if (mb_strlen($term) < 2) {
            return response()->json(['message' => 'Enter at least two characters.'], 422);
        }

        $result = $client->search($term, 8);

        return response()->json($result, $result['available'] ? 200 : 503);
    }
}
