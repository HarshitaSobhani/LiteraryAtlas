<?php

namespace App\Services;

use App\Models\ReadingEntry;
use App\Models\User;

/** Statistics derived only from the user's stored reading entries. */
class ReadingStats
{
    public function for(User $user): array
    {
        $entries = $user->readingEntries()->with('book:id,genre')->get();

        $byStatus = collect(array_keys(ReadingEntry::STATUSES))
            ->mapWithKeys(fn ($s) => [$s => $entries->where('status', $s)->count()])->all();

        $rated = $entries->whereNotNull('rating');

        return [
            'total' => $entries->count(),
            'by_status' => $byStatus,
            'completed_this_year' => $entries->where('status', 'completed')
                ->filter(fn ($e) => $e->finished_at?->isCurrentYear())->count(),
            'average_rating' => $rated->isEmpty() ? null : round($rated->avg('rating'), 1),
            'rated_count' => $rated->count(),
            'genres' => $entries->pluck('book.genre')->filter()->countBy()->sortDesc()->take(5)
                ->map(fn ($count, $genre) => ['genre' => $genre, 'count' => $count])->values()->all(),
        ];
    }
}
