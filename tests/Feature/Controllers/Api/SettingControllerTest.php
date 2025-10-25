<?php

namespace Tests\Feature\Controllers\Api;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class SettingControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_if_settings_can_be_fetched(): void
    {
        $this->seed();

        $this->actingAsAdmin();

        $response = $this->getJson('/api/setting');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'message',
            'data' => [
                'minBookingLength',
                'maxBookingLength',
                'maxGuestsPerBooking',
                'breakfastPrice',
            ],
        ]);

        $response->assertJson(function (AssertableJson $json) {
            $json->whereAllType([
                'success' => 'boolean',
                'message' => 'string|null',
                'errors' => 'array|null',
                'error_code' => 'string|null|integer',
                'data.id' => 'integer',
                'data.minBookingLength' => 'integer',
                'data.maxBookingLength' => 'integer',
                'data.maxGuestsPerBooking' => 'integer',
                'data.breakfastPrice' => 'string',
            ]);
        });
    }

    public function test_if_settings_can_be_updated(): void
    {
        $this->seed();

        $this->actingAsAdmin();

        $response = $this->putJson('/api/setting', [
            'minBookingLength' => 2,
            'maxBookingLength' => 20,
            'maxGuestsPerBooking' => 4,
            'breakfastPrice' => 15.50,
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'message',
            'data' => [
                'minBookingLength',
                'maxBookingLength',
                'maxGuestsPerBooking',
                'breakfastPrice',
            ],
        ]);
        $this->assertDatabaseHas('settings', [
            'minBookingLength' => 2,
            'maxBookingLength' => 20,
            'maxGuestsPerBooking' => 4,
            'breakfastPrice' => 15.50,
        ]);
    }

    public function test_if_settings_can_be_updated_without_default_settings_exists(): void
    {
        $this->seed();

        $this->actingAsAdmin();

        Setting::query()->delete();

        $response = $this->putJson('/api/setting', [
            'minBookingLength' => 2,
            'maxBookingLength' => 20,
            'maxGuestsPerBooking' => 4,
            'breakfastPrice' => 15.50,
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'message',
            'data' => [
                'minBookingLength',
                'maxBookingLength',
                'maxGuestsPerBooking',
                'breakfastPrice',
            ],
        ]);
        $this->assertDatabaseHas('settings', [
            'minBookingLength' => 2,
            'maxBookingLength' => 20,
            'maxGuestsPerBooking' => 4,
            'breakfastPrice' => 15.50,
        ]);
    }

    public function test_if_settings_cannot_be_updated_by_not_authenticated_users(): void
    {
        $this->seed();

        $response = $this->putJson('/api/setting', [
            'minBookingLength' => 2,
            'maxBookingLength' => 20,
            'maxGuestsPerBooking' => 4,
            'breakfastPrice' => 15.50,
        ]);

        $response->assertStatus(401);
    }

    public function test_if_settings_cannot_be_updated_by_not_unauthorized_user(): void
    {
        $this->seed();

        $user = User::factory(1)->create()->first();

        $this->actingAs($user);

        $response = $this->putJson('/api/setting', [
            'minBookingLength' => 2,
            'maxBookingLength' => 20,
            'maxGuestsPerBooking' => 4,
            'breakfastPrice' => 15.50,
        ]);

        $response->assertStatus(403);
    }
}
