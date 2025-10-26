<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Setting::firstOrCreate([
            'minBookingLength' => 1,
            'maxBookingLength' => 30,
            'maxGuestsPerBooking' => 10,
            'breakfastPrice' => 15.00,
        ]);
    }
}
