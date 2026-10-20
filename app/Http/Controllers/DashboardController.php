<?php

namespace App\Http\Controllers;

use App\Services\ReadingStats;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request, ReadingStats $stats): Response
    {
        $user = $request->user();

        return Inertia::render('Dashboard', [
            'reading' => $user->readingEntries()->where('status', 'reading')->with('book.authors')
                ->orderByDesc('updated_at')->limit(6)->get(),
            'wantToRead' => $user->readingEntries()->where('status', 'want_to_read')->with('book.authors')
                ->orderByDesc('updated_at')->limit(6)->get(),
            'recentlyViewed' => $user->bookViews()->with('book.authors')->orderByDesc('viewed_at')->limit(6)->get()
                ->pluck('book')->filter()->values(),
            'savedLocations' => $user->savedLocations()->with('location:id,name,slug,type,city,country')
                ->latest()->limit(6)->get()->pluck('location')->filter()->values(),
            'itineraries' => $user->itineraries()->withCount('stops')->orderByDesc('updated_at')->limit(4)->get(),
            'stats' => $stats->for($user),
        ]);
    }
}
