<?php

namespace Tests\Feature\Controllers\Api;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class SettingControllerTest extends TestCase
{
    use RefreshDatabase;

    public function testIfSettingsCanBeFetched(): void
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
            $json->whereType('success', 'boolean');
            $json->whereType('message', 'string|null');
            $json->whereType('errors', 'array|null');
            $json->whereType('error_code', 'string|null|integer');
            $json->whereType('data.id', "integer");
            $json->whereType('data.minBookingLength', "integer")
                ->whereType('data.maxBookingLength', "integer")
                ->whereType('data.maxGuestsPerBooking', "integer")
                ->whereType('data.breakfastPrice', "string");
        });
    }

    public function testIfSettingsCanBeUpdated(): void
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

    public function testIfSettingsCanBeUpdatedWithoutDefaultSettingsExists(): void
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

    public function testIfSettingsCannotBeUpdatedByNotAuthenticatedUsers(): void
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

    public function testIfSettingsCannotBeUpdatedByNotUnauthorizedUser(): void
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
