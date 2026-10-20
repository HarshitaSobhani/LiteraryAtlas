<?php

namespace Database\Factories;

use App\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Location> */
class LocationFactory extends Factory
{
    protected $model = Location::class;

    public function definition(): array
    {
        $name = fake()->unique()->streetName();

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1, 99999),
            'type' => fake()->randomElement(array_keys(Location::TYPES)),
            'country' => 'Testland',
            'city' => fake()->city(),
            'latitude' => fake()->latitude(35, 55),
            'longitude' => fake()->longitude(-5, 20),
            'coordinates_verified' => true,
            'summary' => fake()->sentence(),
            'significance' => fake()->paragraph(),
        ];
    }

    public function unverified(): static
    {
        return $this->state(['coordinates_verified' => false, 'latitude' => null, 'longitude' => null]);
    }
}
