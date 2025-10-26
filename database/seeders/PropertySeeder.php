<?php

namespace Database\Seeders;

use App\Models\Property;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class PropertySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Property::factory(50)->make()->each(function ($property) {
            $rand = '00'.mt_rand(1, 8);

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
    }
}
