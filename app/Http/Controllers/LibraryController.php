<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReadingEntryRequest;
use App\Models\ReadingEntry;
use App\Services\ReadingStats;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class LibraryController extends Controller
{
    public function index(Request $request, ReadingStats $stats): Response
    {
        $user = $request->user();
        $status = $request->query('status');
        $status = array_key_exists($status, ReadingEntry::STATUSES) ? $status : null;

        $entries = $user->readingEntries()->with('book.authors')
            ->when($status, fn ($q) => $q->where('status', $status))
            ->orderByDesc('updated_at')->get();

        return Inertia::render('Library/Index', [
            'entries' => $entries,
            'status' => $status ?? '',
            'statuses' => ReadingEntry::STATUSES,
            'stats' => $stats->for($user),
            'savedLocations' => $user->savedLocations()->with('location:id,name,slug,type,city,country,summary')
                ->latest()->get()->pluck('location')->filter()->values(),
        ]);
    }

    public function store(ReadingEntryRequest $request): RedirectResponse
    {
        $data = $request->validated();

        // firstOrCreate on the unique (user, book) pair prevents duplicate records.
        $entry = $request->user()->readingEntries()->firstOrNew(['book_id' => $data['book_id']]);
        $existing = $entry->exists;
        $entry->fill($this->normalise($entry, ['status' => $data['status']]))->save();

        return back()->with('status', $existing ? 'Reading status updated.' : 'Added to your library.');
    }

    public function update(ReadingEntryRequest $request, ReadingEntry $entry): RedirectResponse
    {
        Gate::authorize('manage', $entry);
        $entry->fill($this->normalise($entry, $request->validated()))->save();

        return back()->with('status', 'Saved.');
    }

    public function destroy(ReadingEntry $entry): RedirectResponse
    {
        Gate::authorize('manage', $entry);
        $entry->delete();

        return back()->with('status', 'Removed from your library.');
    }

    /** Keep status, progress and dates consistent. */
    private function normalise(ReadingEntry $entry, array $data): array
    {
        $status = $data['status'] ?? $entry->status ?? 'want_to_read';

        if ($status === 'completed') {
            $data['progress'] = 100;
            $data['finished_at'] = $entry->finished_at ?? now();
            $data['started_at'] = $entry->started_at ?? now();
        } elseif ($status === 'reading') {
            $data['started_at'] = $entry->started_at ?? now();
            $data['finished_at'] = null;
        } elseif ($status === 'want_to_read') {
            $data['progress'] = 0;
            $data['finished_at'] = null;
        }

        $data['status'] = $status;

        return $data;
    }
}
