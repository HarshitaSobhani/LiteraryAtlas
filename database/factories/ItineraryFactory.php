<?php

namespace Database\Factories;

use App\Models\Itinerary;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Itinerary> */
class ItineraryFactory extends Factory
{
    protected $model = Itinerary::class;

    public function definition(): array
    {
        return ['user_id' => User::factory(), 'name' => fake()->words(3, true)];
    }
}
