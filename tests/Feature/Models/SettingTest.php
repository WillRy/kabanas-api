<?php

namespace Tests\Feature\Models;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class SettingTest extends TestCase
{
    use RefreshDatabase;

    public function testIfCanReadSettings(): void
    {
        $settingsModel = new \App\Models\Setting();

        $settings = $settingsModel->getSettings();

        $this->assertEquals($settingsModel->first()->toArray(), $settings->toArray());
    }

    public function testIfSettingsAreInitializedWhenNoneExist(): void
    {
        $settingsModel = new \App\Models\Setting();

        $settingsModel->initializeSettings();

        $this->assertDatabaseHas('settings', $settingsModel->defaultSettings);
    }

    public function testIfExistingSettingsIsReturnedWhenCreatingNew(): void
    {
        $settingsModel = new \App\Models\Setting();

        $settingsModel->initializeSettings();
        $settingsModel->initializeSettings();

        $this->assertDatabaseCount('settings', 1);
    }

    public function testIfUnauthorizedUserCannotChangeSettings(): void
    {

        $user = (new User())->createUser([
            'name' => 'Test User',
            'email' => 'email@email.com',
            'password' => 'password123',
        ]);

        $this->actingAs($user);

        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);

        $settingsModel = new \App\Models\Setting();

        $settingsModel->updateSettings([
            'minBookingLength' => 2,
        ]);
    }

    public function testIfAuthorizedUserCanChangeSettings(): void
    {
        $this->seed();

        $user = User::getMasterAdmin();

        $this->actingAs($user);

        $settingsModel = new \App\Models\Setting();

        $settingsModel->updateSettings([
            'minBookingLength' => 2,
        ]);

        $this->assertDatabaseHas('settings', [
            'minBookingLength' => 2,
        ]);
    }

    public function testIfSettingsIsInitializedWhenUpdatingWithoutExists(): void
    {

        $this->seed();

        $user = User::getMasterAdmin();

        $this->actingAs($user);

        $settingsModel = new \App\Models\Setting();

        Setting::query()->delete();

        $settingsModel->updateSettings([
            'minBookingLength' => 2,
        ]);

        $this->assertDatabaseHas('settings', [
            'minBookingLength' => 2,
        ]);
    }
}
