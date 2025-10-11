<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Storage;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Properties>
 */
class PropertyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {


        return [
            'name' => $this->faker->company(),
            'maxCapacity' => $this->faker->numberBetween(1, 20),
            'regularPrice' => $this->faker->randomFloat(2, 50, 500),
            'discount' => $this->faker->boolean(50) ? $this->faker->randomFloat(2, 10, 15) : null,
            'description' => $this->faker->paragraph(),
            'image' => null,
        ];
    }
}
