<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Trip;
use App\Models\TripLeg;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TripLeg>
 */
class TripLegFactory extends Factory
{
    protected $model = TripLeg::class;

    public function definition(): array
    {
        return [
            'trip_id' => Trip::factory(),
            'leg_number' => fake()->numberBetween(2, 5),
            'leg_type' => fake()->randomElement(['return', 'backhaul']),
            'client_id' => Client::factory(),
            'origin' => fake()->randomElement(['MWANZA', 'DODOMA', 'ARUSHA']),
            'destination' => fake()->randomElement(['DAR ES SALAAM', 'MWANZA', 'DODOMA']),
            'payload_weight_tons' => fake()->randomFloat(2, 5, 30),
            'commodity_category' => fake()->randomElement(['agri', 'industrial', 'consumer', 'construction']),
            'distance_km' => fake()->randomFloat(2, 50, 1500),
            'status' => 'pending',
        ];
    }
}
