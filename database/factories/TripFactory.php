<?php

namespace Database\Factories;

use App\Enums\TripStatus;
use App\Models\Client;
use App\Models\Driver;
use App\Models\Trip;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Trip>
 */
class TripFactory extends Factory
{
    protected $model = Trip::class;

    public function definition(): array
    {
        $origin = fake()->randomElement(['DAR ES SALAAM', 'MWANZA', 'DODOMA', 'ARUSHA', 'KILIMANJARO']);
        $destination = fake()->randomElement(['DAR ES SALAAM', 'MWANZA', 'DODOMA', 'ARUSHA', 'KILIMANJARO']);

        while ($destination === $origin) {
            $destination = fake()->randomElement(['DAR ES SALAAM', 'MWANZA', 'DODOMA', 'ARUSHA', 'KILIMANJARO']);
        }

        return [
            'trip_number' => 'TR-' . fake()->unique()->numerify('######'),
            'client_id' => Client::factory(),
            'vehicle_id' => Vehicle::factory(),
            'driver_id' => Driver::factory(),
            'origin' => $origin,
            'destination' => $destination,
            'total_distance_km' => fake()->randomFloat(2, 100, 2000),
            'allocated_payload_tons' => fake()->randomElement([10, 15, 20, 25, 30]),
            'status' => fake()->randomElement(TripStatus::cases()),
            'estimated_fuel_cost' => fake()->randomFloat(2, 100, 5000),
            'estimated_expenses' => fake()->randomFloat(2, 200, 10000),
        ];
    }
}
