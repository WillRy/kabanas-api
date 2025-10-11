<?php

namespace Database\Seeders;

use App\Models\Property;
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

        User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@admin.com',
        ]);

        Property::factory(50)->make()->each(function ($property) {
            $rand = "00".mt_rand(1, 8);

            $src = storage_path("demo/properties/cabin-{$rand}.jpg");

            $destPath = "properties/cabin-{$rand}.jpg";

            if (file_exists($src)) {
                Storage::disk('public')->put($destPath, file_get_contents($src));
                $url = Storage::url($destPath);
            } else {
                $url = null;
            }


            $property->image = $url;
            $property->save();
        });
    }
}
