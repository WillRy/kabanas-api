<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Booking>
 */
class BookingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $hasBreakfast = $this->faker->boolean();
        return [
            'startDate' => $this->faker->dateTimeBetween('+1 days', '+1 month'),
            'endDate' => $this->faker->dateTimeBetween('+2 days', '+2 months'),
            'numNights' => $this->faker->numberBetween(1, 14),
            'numGuests' => $this->faker->numberBetween(1, 6),
            'propertyPrice' => $this->faker->randomFloat(2, 50, 500),
            'extrasPrice' => $hasBreakfast ? 15.00 : 0.00,
            'totalPrice' => function (array $attributes) {
                return $attributes['propertyPrice'] + $attributes['extrasPrice'];
            },
            'status' => $this->faker->randomElement(['checked-in', 'checked-out', 'unconfirmed']),
            'hasBreakfast' => $hasBreakfast,
            'isPaid' => $this->faker->boolean(),
            'observations' => $this->faker->optional()->sentence(),
            'guest_id' => \App\Models\Guest::factory(),
            'property_id' => \App\Models\Property::factory(),
        ];
    }
}
