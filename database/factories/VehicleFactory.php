<?php

namespace Database\Factories;

use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Vehicle>
 */
class VehicleFactory extends Factory
{
    protected $model = Vehicle::class;

    public function definition(): array
    {
        return [
            'plate_number' => strtoupper(fake()->bothify('??-####')),
            'make' => fake()->randomElement(['Toyota', 'Nissan', 'Hino', 'Volvo', 'Scania', 'MAN']),
            'model' => fake()->randomElement(['Dynard', 'Canter', 'FH', 'R-series', 'TGS']),
            'year' => fake()->numberBetween(2015, 2025),
            'capacity_tons' => fake()->randomElement([5, 10, 15, 20, 25, 30]),
            'fuel_type' => fake()->randomElement(['diesel', 'petrol']),
            'status' => 'active',
        ];
    }
}
