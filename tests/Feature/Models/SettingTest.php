<?php

namespace Tests\Feature\Models;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingTest extends TestCase
{
    use RefreshDatabase;

    public function test_if_can_read_settings(): void
    {
        $settingsModel = new \App\Models\Setting;

        $settings = $settingsModel->getSettings();

        $this->assertEquals($settingsModel->first()->toArray(), $settings->toArray());
    }

    public function test_if_settings_are_initialized_when_none_exist(): void
    {
        $settingsModel = new \App\Models\Setting;

        $settingsModel->initializeSettings();

        $this->assertDatabaseHas('settings', $settingsModel->defaultSettings);
    }

    public function test_if_existing_settings_is_returned_when_creating_new(): void
    {
        $settingsModel = new \App\Models\Setting;

        $settingsModel->initializeSettings();
        $settingsModel->initializeSettings();

        $this->assertDatabaseCount('settings', 1);
    }

    public function test_if_unauthorized_user_cannot_change_settings(): void
    {

        $user = (new User)->createUser([
            'name' => 'Test User',
            'email' => 'email@email.com',
            'password' => 'password123',
        ]);

        $this->actingAs($user);

        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);

        $settingsModel = new \App\Models\Setting;

        $settingsModel->updateSettings([
            'minBookingLength' => 2,
        ]);
    }

    public function test_if_authorized_user_can_change_settings(): void
    {
        $this->seed();

        $this->actingAsAdmin();

        $settingsModel = new \App\Models\Setting;

        $settingsModel->updateSettings([
            'minBookingLength' => 2,
        ]);

        $this->assertDatabaseHas('settings', [
            'minBookingLength' => 2,
        ]);
    }

    public function test_if_settings_is_initialized_when_updating_without_exists(): void
    {

        $this->seed();

        $this->actingAsAdmin();

        $settingsModel = new \App\Models\Setting;

        Setting::query()->delete();

        $settingsModel->updateSettings([
            'minBookingLength' => 2,
        ]);

        $this->assertDatabaseHas('settings', [
            'minBookingLength' => 2,
        ]);
    }
}
