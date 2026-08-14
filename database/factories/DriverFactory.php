<?php

namespace Database\Factories;

use App\Models\Driver;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Driver>
 */
class DriverFactory extends Factory
{
    protected $model = Driver::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'license_number' => strtoupper(fake()->bothify('??-######')),
            'vehicle_id' => Vehicle::factory(),
            'status' => 'available',
        ];
    }
}
