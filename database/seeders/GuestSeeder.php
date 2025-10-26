<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Guest;
use App\Models\Property;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class GuestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $guestRole = Role::where('name', 'guest')->first();





        User::factory(50)->create()->each(function ($user) use ($guestRole) {
            $user->roles()->syncWithoutDetaching([$guestRole->id]);

            Guest::factory(1)->create([
                'user_id' => $user->id,
            ]);

            Booking::factory(1)->create([
                'guest_id' => $user->guestProfile->id,
                'property_id' => Property::inRandomOrder()->first()->id,
                'startDate' => now()->subMonth(1)->format('Y-m-d'),
                'endDate' => now()->subMonth(1)->format('Y-m-d'),
            ]);
        });

        // Bookings in last 7 days
        User::factory(5)->create()->each(function ($user) use ($guestRole) {
            $user->roles()->syncWithoutDetaching([$guestRole->id]);
            Guest::factory(1)->create(['user_id' => $user->id]);
            Booking::factory(1)->create([
                'guest_id' => $user->guestProfile->id,
                'property_id' => Property::inRandomOrder()->first()->id,
                'startDate' => now()->subDays(rand(1, 7))->format('Y-m-d'),
                'created_at' => now()->subDays(rand(1, 7))->format('Y-m-d'),
                'endDate' => now()->format('Y-m-d'),
                'status' => 'checked-out',
            ]);
        });

        // Bookings in last 30 days
        User::factory(3)->create()->each(function ($user) use ($guestRole) {
            $user->roles()->syncWithoutDetaching([$guestRole->id]);
            Guest::factory(1)->create(['user_id' => $user->id]);
            Booking::factory(1)->create([
                'guest_id' => $user->guestProfile->id,
                'property_id' => Property::inRandomOrder()->first()->id,
                'startDate' => now()->subDays(rand(8, 30))->format('Y-m-d'),
                'created_at' => now()->subDays(rand(8, 30))->format('Y-m-d'),
                'endDate' => now()->subDays(rand(1, 7))->format('Y-m-d'),
                'status' => 'checked-out',
            ]);
        });

        // Bookings in last 90 days
        User::factory(2)->create()->each(function ($user) use ($guestRole) {
            $user->roles()->syncWithoutDetaching([$guestRole->id]);
            Guest::factory(1)->create(['user_id' => $user->id]);
            Booking::factory(1)->create([
                'guest_id' => $user->guestProfile->id,
                'property_id' => Property::inRandomOrder()->first()->id,
                'startDate' => now()->subDays(rand(31, 90))->format('Y-m-d'),
                'created_at' => now()->subDays(rand(31, 90))->format('Y-m-d'),
                'endDate' => now()->subDays(rand(8, 30))->format('Y-m-d'),
                'status' => 'checked-out',
            ]);
        });

        User::factory(5)->create()->each(function ($user) use ($guestRole) {
            $user->roles()->syncWithoutDetaching([$guestRole->id]);

            Guest::factory(1)->create([
                'user_id' => $user->id,
            ]);

            Booking::factory(1)->create([
                'guest_id' => $user->guestProfile->id,
                'property_id' => Property::inRandomOrder()->first()->id,
                'startDate' => now()->format('Y-m-d'),
                'endDate' => now()->addDays(3)->format('Y-m-d'),
                'status' => 'unconfirmed',
            ]);
        });

        User::factory(5)->create()->each(function ($user) use ($guestRole) {
            $user->roles()->syncWithoutDetaching([$guestRole->id]);

            Guest::factory(1)->create([
                'user_id' => $user->id,
            ]);

            Booking::factory(1)->create([
                'guest_id' => $user->guestProfile->id,
                'property_id' => Property::inRandomOrder()->first()->id,
                'startDate' => now()->subDays(3)->format('Y-m-d'),
                'endDate' => now()->format('Y-m-d'),
                'status' => 'checked-in',
            ]);
        });
    }
}
