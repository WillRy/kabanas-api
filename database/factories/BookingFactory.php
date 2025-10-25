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

        $settings = \App\Models\Setting::first();

        if (! $settings) {
            $settings = (new \App\Models\Setting)->initializeSettings();
        }

        return [
            'startDate' => $this->faker->dateTimeBetween('+1 days', '+1 month'),
            'endDate' => $this->faker->dateTimeBetween('+2 days', '+2 months'),
            'numNights' => $this->faker->numberBetween(1, 14),
            'numGuests' => $this->faker->numberBetween(1, 6),
            'propertyPrice' => $this->faker->randomFloat(2, 50, 500),
            'hasBreakfast' => $hasBreakfast,
            'extrasPrice' => function (array $attributes) use ($settings) {
                return round($attributes['hasBreakfast']
                    ? $settings->breakfastPrice * $attributes['numNights'] * $attributes['numGuests']
                    : 0, 2);
            },
            'totalPrice' => function (array $attributes) {
                return round(($attributes['propertyPrice'] * $attributes['numNights']) + $attributes['extrasPrice'], 2);
            },
            'status' => $this->faker->randomElement(['checked-in', 'checked-out', 'unconfirmed']),

            'isPaid' => $this->faker->boolean(),
            'observations' => $this->faker->optional()->sentence(),
            'guest_id' => \App\Models\Guest::factory(),
            'property_id' => \App\Models\Property::factory(),
        ];
    }
}
