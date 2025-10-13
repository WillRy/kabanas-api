<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Guest>
 */
class GuestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $country = $this->faker->countryCode();
        $countryFlag = "https://flagcdn.com/{$country}.svg";
        return [
            'nationalID' => $this->faker->unique()->numerify('###########'),
            'nationality' => $this->faker->country(),
            'countryFlag' => $countryFlag,
        ];
    }
}
