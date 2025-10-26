<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $manager = Role::firstOrCreate(['name' => 'manager']);

        $manageProperties = Permission::firstOrCreate(['name' => 'manage-properties']);
        $manageSettings = Permission::firstOrCreate(['name' => 'settings']);
        $manageBookings = Permission::firstOrCreate(['name' => 'manage-bookings']);

        Role::firstOrCreate(['name' => 'guest']);

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
    }
}
