<?php

namespace Tests\Feature;

use App\Models\Itinerary;
use App\Models\Location;
use App\Models\User;
use App\Services\Geo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ItineraryTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_rename_and_delete(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/itineraries', ['name' => 'Paris trip', 'description' => 'Spring']);
        $it = Itinerary::first();
        $response->assertRedirect("/itineraries/{$it->id}");
        $this->assertSame($user->id, $it->user_id);

        $this->actingAs($user)->patch("/itineraries/{$it->id}", ['name' => 'Renamed'])->assertSessionHasNoErrors();
        $this->assertSame('Renamed', $it->fresh()->name);

        $this->actingAs($user)->delete("/itineraries/{$it->id}")->assertRedirect('/itineraries');
        $this->assertModelMissing($it);
    }

    public function test_name_is_required_and_bounded(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/itineraries', ['name' => ''])->assertSessionHasErrors('name');
        $this->actingAs($user)->post('/itineraries', ['name' => str_repeat('a', 121)])->assertSessionHasErrors('name');
    }

    public function test_other_users_cannot_view_change_or_delete_an_itinerary(): void
    {
        $it = Itinerary::factory()->create();
        $loc = Location::factory()->create();
        $intruder = User::factory()->create();

        $this->actingAs($intruder)->get("/itineraries/{$it->id}")->assertForbidden();
        $this->actingAs($intruder)->patch("/itineraries/{$it->id}", ['name' => 'x'])->assertForbidden();
        $this->actingAs($intruder)->delete("/itineraries/{$it->id}")->assertForbidden();
        $this->actingAs($intruder)->post("/itineraries/{$it->id}/stops", ['location_id' => $loc->id])->assertForbidden();
        $this->assertModelExists($it);
    }

    public function test_index_lists_only_own_itineraries(): void
    {
        $user = User::factory()->create();
        Itinerary::factory()->create(['user_id' => $user->id]);
        Itinerary::factory()->create();

        $this->actingAs($user)->get('/itineraries')->assertInertia(fn (Assert $p) => $p->has('itineraries', 1));
    }

    public function test_stops_are_added_in_order_without_duplicates(): void
    {
        $it = Itinerary::factory()->create();
        [$a, $b] = Location::factory()->count(2)->create();

        $this->actingAs($it->user)->post("/itineraries/{$it->id}/stops", ['location_id' => $a->id]);
        $this->actingAs($it->user)->post("/itineraries/{$it->id}/stops", ['location_id' => $b->id]);
        $this->actingAs($it->user)->post("/itineraries/{$it->id}/stops", ['location_id' => $a->id])->assertSessionHas('error');

        $this->assertSame([$a->id, $b->id], $it->stops()->pluck('location_id')->all());
        $this->assertSame([1, 2], $it->stops()->pluck('position')->all());
    }

    public function test_stop_details_are_validated_and_saved(): void
    {
        $it = Itinerary::factory()->create();
        $stop = $it->stops()->create(['location_id' => Location::factory()->create()->id, 'position' => 1]);

        $this->actingAs($it->user)->patch("/itineraries/{$it->id}/stops/{$stop->id}", [
            'visit_date' => '2027-05-04', 'duration_minutes' => 90, 'notes' => 'Bring a book',
        ])->assertSessionHasNoErrors();
        $stop->refresh();
        $this->assertSame('2027-05-04', $stop->visit_date->toDateString());
        $this->assertSame(90, $stop->duration_minutes);

        $this->actingAs($it->user)->patch("/itineraries/{$it->id}/stops/{$stop->id}", ['visit_date' => 'tomorrow'])->assertSessionHasErrors('visit_date');
        $this->actingAs($it->user)->patch("/itineraries/{$it->id}/stops/{$stop->id}", ['duration_minutes' => 0])->assertSessionHasErrors('duration_minutes');
        $this->actingAs($it->user)->patch("/itineraries/{$it->id}/stops/{$stop->id}", ['location_id' => 5])->assertSessionHasErrors('location_id');
    }

    public function test_stops_can_be_reordered_and_removed_with_renumbering(): void
    {
        $it = Itinerary::factory()->create();
        $stops = Location::factory()->count(3)->create()->map(
            fn ($l, $i) => $it->stops()->create(['location_id' => $l->id, 'position' => $i + 1])
        );

        $new = [$stops[2]->id, $stops[0]->id, $stops[1]->id];
        $this->actingAs($it->user)->put("/itineraries/{$it->id}/stops/order", ['order' => $new])->assertSessionHasNoErrors();
        $this->assertSame($new, $it->stops()->pluck('id')->all());

        $this->actingAs($it->user)->delete("/itineraries/{$it->id}/stops/{$stops[0]->id}");
        $this->assertSame([1, 2], $it->stops()->pluck('position')->all());
    }

    public function test_reorder_rejects_foreign_or_incomplete_lists(): void
    {
        $it = Itinerary::factory()->create();
        $mine = $it->stops()->create(['location_id' => Location::factory()->create()->id, 'position' => 1]);
        $other = Itinerary::factory()->create()->stops()->create(['location_id' => Location::factory()->create()->id, 'position' => 1]);

        $this->actingAs($it->user)->put("/itineraries/{$it->id}/stops/order", ['order' => [$other->id]])->assertSessionHasErrors('order.0');
        $this->actingAs($it->user)->put("/itineraries/{$it->id}/stops/order", ['order' => []])->assertSessionHasErrors('order');
        $this->assertSame(1, $mine->fresh()->position);
    }

    public function test_stop_of_another_itinerary_cannot_be_touched_through_own_url(): void
    {
        $it = Itinerary::factory()->create();
        $foreign = Itinerary::factory()->create()->stops()->create(['location_id' => Location::factory()->create()->id, 'position' => 1]);

        $this->actingAs($it->user)->delete("/itineraries/{$it->id}/stops/{$foreign->id}")->assertNotFound();
        $this->actingAs($it->user)->patch("/itineraries/{$it->id}/stops/{$foreign->id}", ['notes' => 'x'])->assertNotFound();
        $this->assertModelExists($foreign);
    }

    public function test_show_computes_straight_line_legs_and_labels_directions(): void
    {
        $it = Itinerary::factory()->create();
        $paris = Location::factory()->create(['latitude' => 48.8566, 'longitude' => 2.3522]);
        $london = Location::factory()->create(['latitude' => 51.5074, 'longitude' => -0.1278]);
        $it->stops()->create(['location_id' => $paris->id, 'position' => 1, 'duration_minutes' => 60]);
        $it->stops()->create(['location_id' => $london->id, 'position' => 2, 'duration_minutes' => 30]);

        $this->actingAs($it->user)->get("/itineraries/{$it->id}")->assertInertia(fn (Assert $p) => $p
            ->component('Itineraries/Show')
            ->has('legs', 1)
            ->where('legs.0.straight_line_km', fn ($km) => abs($km - 343.6) < 2)
            ->where('totalMinutes', 90)
            ->where('legs.0.directions_url', fn ($u) => str_starts_with($u, 'https://www.openstreetmap.org/directions')));
    }

    public function test_haversine_is_accurate(): void
    {
        $this->assertEqualsWithDelta(0.0, Geo::haversineKm(10, 10, 10, 10), 0.0001);
        $this->assertEqualsWithDelta(343.6, Geo::haversineKm(48.8566, 2.3522, 51.5074, -0.1278), 1.0);
    }

    public function test_deleting_a_user_cascades_personal_data(): void
    {
        $it = Itinerary::factory()->create();
        $it->stops()->create(['location_id' => Location::factory()->create()->id, 'position' => 1]);

        $it->user->delete();

        $this->assertDatabaseCount('itineraries', 0);
        $this->assertDatabaseCount('itinerary_stops', 0);
        $this->assertDatabaseCount('locations', 1);
    }
}
