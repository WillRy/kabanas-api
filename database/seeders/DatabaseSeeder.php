<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Guest;
use App\Models\Permission;
use App\Models\Property;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $manager = Role::firstOrCreate(['name' => 'manager']);

        $manageProperties = Permission::firstOrCreate(['name' => 'manage-properties']);
        $manageSettings = Permission::firstOrCreate(['name' => 'manage-settings']);
        $manageBookings = Permission::firstOrCreate(['name' => 'manage-bookings']);
        $manageSettings = Permission::firstOrCreate(['name' => 'settings']);

        $guestRole = Role::firstOrCreate(['name' => 'guest']);

        $manager->permissions()->syncWithoutDetaching([$manageProperties->id, $manageSettings->id, $manageBookings->id]);


        $admin = User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@admin.com',
        ]);

        $admin->tokens()->create([
            'name' => 'admin-token',
            'token' => hash('sha256', '7cxeNgCt3HAPY5FZZfdckKpkslZz3XFp7rsED7CI1949de93'),
            'abilities' => ['*'],
        ]);

        $admin->roles()->syncWithoutDetaching([$manager->id]);

        Property::factory(50)->make()->each(function ($property) {
            $rand = "00" . mt_rand(1, 8);

            $src = storage_path("demo/properties/cabin-{$rand}.jpg");

            $destPath = "/properties/cabin-{$rand}.jpg";

            if (file_exists($src)) {
                Storage::disk('public')->put($destPath, file_get_contents($src));
                $url = $destPath;
            } else {
                $url = null;
            }


            $property->image = $url;
            $property->save();
        });

        Setting::firstOrCreate([
            'minBookingLength' => 1,
            'maxBookingLength' => 30,
            'maxGuestsPerBooking' => 10,
            'breakfastPrice' => 15.00
        ]);

        User::factory(50)->create()->each(function ($user) use ($guestRole) {
            $user->roles()->syncWithoutDetaching([$guestRole->id]);


            Guest::factory(1)->create([
                'user_id' => $user->id,
            ]);


            Booking::factory(1)->create([
                'guest_id' => $user->guestProfile->id,
                'property_id' => Property::inRandomOrder()->first()->id,
            ]);
        });
    }
}
