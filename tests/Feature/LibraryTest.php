<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Location;
use App\Models\ReadingEntry;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class LibraryTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_from_personal_pages(): void
    {
        foreach (['/library', '/dashboard', '/itineraries'] as $url) {
            $this->get($url)->assertRedirect('/login');
        }
        $this->post('/library', [])->assertRedirect('/login');
    }

    public function test_user_can_add_a_book_and_duplicates_are_prevented(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $this->actingAs($user)->post('/library', ['book_id' => $book->id, 'status' => 'want_to_read'])
            ->assertSessionHas('status');
        $this->actingAs($user)->post('/library', ['book_id' => $book->id, 'status' => 'reading'])
            ->assertSessionHas('status');

        $this->assertSame(1, ReadingEntry::where('user_id', $user->id)->count());
        $this->assertSame('reading', ReadingEntry::first()->status);
        $this->assertNotNull(ReadingEntry::first()->started_at);
    }

    public function test_database_rejects_duplicate_entries(): void
    {
        $entry = ReadingEntry::factory()->create();

        $this->expectException(QueryException::class);
        ReadingEntry::factory()->create(['user_id' => $entry->user_id, 'book_id' => $entry->book_id]);
    }

    public function test_add_validates_input(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/library', ['book_id' => 9999, 'status' => 'reading'])->assertSessionHasErrors('book_id');
        $this->actingAs($user)->post('/library', ['book_id' => Book::factory()->create()->id, 'status' => 'flying'])->assertSessionHasErrors('status');
    }

    public function test_status_progress_rating_and_notes_update(): void
    {
        $entry = ReadingEntry::factory()->create(['status' => 'reading']);

        $this->actingAs($entry->user)->patch("/library/{$entry->id}", ['progress' => 40, 'rating' => 4, 'notes' => 'Lovely'])->assertSessionHasNoErrors();
        $entry->refresh();
        $this->assertSame([40, 4, 'Lovely'], [$entry->progress, $entry->rating, $entry->notes]);

        $this->actingAs($entry->user)->patch("/library/{$entry->id}", ['status' => 'completed']);
        $entry->refresh();
        $this->assertSame(100, $entry->progress);
        $this->assertNotNull($entry->finished_at);

        $this->actingAs($entry->user)->patch("/library/{$entry->id}", ['status' => 'want_to_read']);
        $this->assertSame(0, $entry->fresh()->progress);
    }

    public function test_update_rejects_out_of_range_values(): void
    {
        $entry = ReadingEntry::factory()->create();

        $this->actingAs($entry->user)->patch("/library/{$entry->id}", ['progress' => 101])->assertSessionHasErrors('progress');
        $this->actingAs($entry->user)->patch("/library/{$entry->id}", ['rating' => 6])->assertSessionHasErrors('rating');
        $this->actingAs($entry->user)->patch("/library/{$entry->id}", ['rating' => 0])->assertSessionHasErrors('rating');
        $this->actingAs($entry->user)->patch("/library/{$entry->id}", ['user_id' => 999, 'rating' => 3]);
        $this->assertSame($entry->user_id, $entry->fresh()->user_id);
    }

    public function test_users_cannot_modify_or_delete_other_users_entries(): void
    {
        $entry = ReadingEntry::factory()->create(['notes' => 'private']);
        $intruder = User::factory()->create();

        $this->actingAs($intruder)->patch("/library/{$entry->id}", ['notes' => 'hacked'])->assertForbidden();
        $this->actingAs($intruder)->delete("/library/{$entry->id}")->assertForbidden();

        $this->assertSame('private', $entry->fresh()->notes);
    }

    public function test_library_and_dashboard_only_show_own_data(): void
    {
        $mine = ReadingEntry::factory()->create(['status' => 'reading', 'notes' => 'my note']);
        ReadingEntry::factory()->create(['status' => 'reading', 'notes' => 'their secret']);

        $this->actingAs($mine->user)->get('/library')->assertInertia(fn (Assert $p) => $p
            ->has('entries', 1)->where('entries.0.notes', 'my note'));
        $this->actingAs($mine->user)->get('/dashboard')->assertInertia(fn (Assert $p) => $p
            ->has('reading', 1)->where('stats.total', 1));
    }

    public function test_notes_are_never_exposed_on_public_pages(): void
    {
        $entry = ReadingEntry::factory()->create(['notes' => 'top secret note']);

        $this->get("/books/{$entry->book->slug}")->assertOk()->assertDontSee('top secret note');
    }

    public function test_stats_are_derived_from_records(): void
    {
        $user = User::factory()->create();
        $books = Book::factory()->count(3)->create(['genre' => 'Mystery']);
        ReadingEntry::factory()->create(['user_id' => $user->id, 'book_id' => $books[0]->id, 'status' => 'completed', 'rating' => 5, 'finished_at' => now()]);
        ReadingEntry::factory()->create(['user_id' => $user->id, 'book_id' => $books[1]->id, 'status' => 'completed', 'rating' => 4, 'finished_at' => now()->subYears(2)]);
        ReadingEntry::factory()->create(['user_id' => $user->id, 'book_id' => $books[2]->id, 'status' => 'reading']);

        $this->actingAs($user)->get('/library')->assertInertia(fn (Assert $p) => $p
            ->where('stats.total', 3)
            ->where('stats.by_status.completed', 2)
            ->where('stats.completed_this_year', 1)
            ->where('stats.average_rating', 4.5)
            ->where('stats.genres.0.genre', 'Mystery'));
    }

    public function test_entry_can_be_removed(): void
    {
        $entry = ReadingEntry::factory()->create();

        $this->actingAs($entry->user)->delete("/library/{$entry->id}")->assertSessionHas('status');
        $this->assertModelMissing($entry);
    }

    public function test_viewing_a_book_records_recently_viewed(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $this->actingAs($user)->get("/books/{$book->slug}");
        $this->actingAs($user)->get("/books/{$book->slug}");

        $this->assertSame(1, $user->bookViews()->count());
        $this->actingAs($user)->get('/dashboard')->assertInertia(fn (Assert $p) => $p->has('recentlyViewed', 1));
    }

    public function test_locations_can_be_saved_once_and_removed(): void
    {
        $user = User::factory()->create();
        $loc = Location::factory()->create();

        $this->actingAs($user)->post('/saved-locations', ['location_id' => $loc->id]);
        $this->actingAs($user)->post('/saved-locations', ['location_id' => $loc->id]);
        $this->assertSame(1, $user->savedLocations()->count());

        $this->actingAs($user)->get("/locations/{$loc->slug}")->assertInertia(fn (Assert $p) => $p->where('isSaved', true));

        $this->actingAs($user)->delete("/saved-locations/{$loc->slug}");
        $this->assertSame(0, $user->savedLocations()->count());
        $this->actingAs($user)->post('/saved-locations', ['location_id' => 9999])->assertSessionHasErrors('location_id');
    }

    public function test_saved_locations_are_private_per_user(): void
    {
        $loc = Location::factory()->create();
        $a = User::factory()->create();
        $b = User::factory()->create();
        $a->savedLocations()->create(['location_id' => $loc->id]);

        $this->actingAs($b)->get('/library')->assertInertia(fn (Assert $p) => $p->has('savedLocations', 0));
    }
}
