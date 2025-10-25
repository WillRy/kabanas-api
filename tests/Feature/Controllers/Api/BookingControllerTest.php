<?php

namespace Tests\Feature\Controllers\Api;

use App\Models\Guest;
use App\Models\Property;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class BookingControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    public function test_if_list_bookings_fails_if_unauthenticated(): void
    {
        $response = $this->getJson('/api/bookings');

        $response->assertStatus(401);
    }

    public function test_if_list_bookings_fails_in_unauthorized_user(): void
    {
        $this->seed();

        $user = \App\Models\User::factory()->create();

        $this->actingAs($user);

        $response = $this->getJson('/api/bookings');

        $response->assertStatus(403);
    }

    public function test_if_list_bookings_works_for_admin(): void
    {
        $this->seed();

        $this->actingAsAdmin();

        $response = $this->getJson('/api/bookings');
        $response->assertStatus(200);
        $response->assertJson(function (AssertableJson $json) {
            $json
                ->has('success')
                ->has('message')
                ->whereAllType([
                    'data' => 'array',
                    'data.data' => 'array',
                    'data.data.0.id' => 'integer',
                    'data.data.0.startDate' => 'string',
                    'data.data.0.endDate' => 'string',
                    'data.data.0.numNights' => 'integer',
                    'data.data.0.numGuests' => 'integer',
                    'data.data.0.propertyPrice' => 'integer|double',
                    'data.data.0.extrasPrice' => 'integer|double|null',
                    'data.data.0.totalPrice' => 'integer|double',
                    'data.data.0.status' => 'string',
                    'data.data.0.isPaid' => 'boolean',
                    'data.data.0.hasBreakfast' => 'boolean',
                    'data.data.0.observations' => 'string|null',
                    'data.data.0.guest' => 'array',
                    'data.data.0.guest.id' => 'integer',
                    'data.data.0.guest.name' => 'string',
                    'data.data.0.guest.email' => 'string',
                    'data.data.0.guest.countryFlag' => 'string',
                    'data.data.0.property.id' => 'integer',
                    'data.data.0.property.name' => 'string',
                    'data.data.0.property.maxCapacity' => 'integer',
                    'data.data.0.property.regularPrice' => 'integer|double',
                    'data.data.0.property.discount' => 'integer|double|null',
                    'data.data.0.property.description' => 'string',
                    'data.data.0.property.image' => 'string|null',
                ])
                ->etc();
        });

        $response = $this->getJson('/api/bookings');
        $response->assertStatus(200);

        $response = $this->getJson('/api/bookings?status=checked-in');
        $response->assertStatus(200);
        $data = $response->json('data.data');
        $statusDifferent = array_filter($data, function ($booking) {
            return $booking['status'] !== 'checked-in';
        });
        $this->assertEmpty($statusDifferent);

        $response = $this->getJson('/api/bookings?status=checked-out');
        $response->assertStatus(200);
        $data = $response->json('data.data');
        $statusDifferent = array_filter($data, function ($booking) {
            return $booking['status'] !== 'checked-out';
        });
        $this->assertEmpty($statusDifferent);

        $response = $this->getJson('/api/bookings?status=unconfirmed');
        $response->assertStatus(200);
        $data = $response->json('data.data');
        $statusDifferent = array_filter($data, function ($booking) {
            return $booking['status'] !== 'unconfirmed';
        });
        $this->assertEmpty($statusDifferent);

        $response = $this->getJson('/api/bookings?sortBy=id&sortOrder=desc');
        $response->assertStatus(200);
        $data = $response->json('data.data');
        $this->assertFalse($data[0]['id'] < $data[1]['id']);

        $response = $this->getJson('/api/bookings?sortBy=startDate&sortOrder=desc');
        $response->assertStatus(200);
        $data = $response->json('data.data');
        $this->assertFalse($data[0]['startDate'] < $data[1]['startDate']);

        $response = $this->getJson('/api/bookings?sortBy=totalPrice&sortOrder=desc');
        $response->assertStatus(200);
        $data = $response->json('data.data');
        $this->assertFalse($data[0]['totalPrice'] < $data[1]['totalPrice']);
    }

    public function test_if_cannot_view_booking_with_unauthorized_user(): void
    {
        $this->seed();

        $user = \App\Models\User::factory()->create();

        $this->actingAs($user);

        $booking = \App\Models\Booking::first();

        $response = $this->getJson('/api/bookings/'.$booking->id);

        $response->assertStatus(403);
    }

    public function test_if_view_booking_fails_with_inexistent_booking(): void
    {
        $this->seed();

        $user = \App\Models\User::factory()->create();

        $this->actingAs($user);

        $response = $this->getJson('/api/bookings/9999');
        $response->assertStatus(404);
    }

    public function test_if_view_booking_works_with_existent_booking(): void
    {
        $this->seed();

        $this->actingAsAdmin();

        $booking = \App\Models\Booking::first();

        $response = $this->getJson('/api/bookings/'.$booking->id);

        $response->assertStatus(200);
        $response->assertJson(function (AssertableJson $json) {
            $json
                ->has('success')
                ->has('message')
                ->whereType('data.id', 'integer')
                ->whereType('data.startDate', 'string')
                ->whereType('data.endDate', 'string')
                ->whereType('data.numNights', 'integer')
                ->whereType('data.numGuests', 'integer')
                ->whereType('data.propertyPrice', 'integer|double')
                ->whereType('data.extrasPrice', 'integer|double|null')
                ->whereType('data.totalPrice', 'integer|double')
                ->whereType('data.status', 'string')
                ->whereType('data.isPaid', 'boolean')
                ->whereType('data.hasBreakfast', 'boolean')
                ->whereType('data.observations', 'string|null')
                ->whereType('data.guest', 'array')
                ->whereType('data.guest.id', 'integer')
                ->whereType('data.guest.name', 'string')
                ->whereType('data.guest.email', 'string')
                ->whereType('data.guest.countryFlag', 'string')
                ->whereType('data.property.id', 'integer')
                ->whereType('data.property.name', 'string')
                ->whereType('data.property.maxCapacity', 'integer')
                ->whereType('data.property.regularPrice', 'double')
                ->whereType('data.property.discount', 'integer|double|null')
                ->whereType('data.property.description', 'string')
                ->whereType('data.property.image', 'string|null')
                ->etc();
        });
    }

    public function test_if_checkin_fails_if_unauthenticated(): void
    {
        $response = $this->putJson('/api/bookings/1/check-in');

        $response->assertStatus(401);
    }

    public function test_if_checkin_fails_in_unauthorized_user(): void
    {
        $this->seed();

        $user = \App\Models\User::factory()->create();

        $this->actingAs($user);

        $response = $this->putJson('/api/bookings/1/check-in');

        $response->assertStatus(403);
    }

    public function test_if_checkin_fails_with_different_status_than_unconfirmed(): void
    {
        $this->seed();

        $this->actingAsAdmin();

        $booking = \App\Models\Booking::where('status', '!=', 'unconfirmed')->first();

        $response = $this->putJson('/api/bookings/'.$booking->id.'/check-in');
        $response->assertStatus(422);
    }

    public function test_if_checkin_fails_with_inexistent_booking(): void
    {
        $this->seed();

        $this->actingAsAdmin();

        $response = $this->putJson('/api/bookings/9999999999/check-in');
        $response->assertStatus(404);
    }

    public function test_if_checkin_works_with_valid_data(): void
    {
        $this->seed();

        $this->actingAsAdmin();

        $booking = \App\Models\Booking::where('status', '=', 'unconfirmed')->first();

        $response = $this->putJson('/api/bookings/'.$booking->id.'/check-in');
        $response->assertStatus(200);

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => 'checked-in',
        ]);
    }

    public function test_if_checkout_fails_if_unauthenticated(): void
    {
        $response = $this->putJson('/api/bookings/1/check-out');

        $response->assertStatus(401);
    }

    public function test_if_checkout_fails_in_unauthorized_user(): void
    {
        $this->seed();

        $user = \App\Models\User::factory()->create();

        $this->actingAs($user);

        $response = $this->putJson('/api/bookings/1/check-out');

        $response->assertStatus(403);
    }

    public function test_if_checkout_fails_with_different_status_than_checked_in(): void
    {
        $this->seed();

        $this->actingAsAdmin();

        $booking = \App\Models\Booking::where('status', '!=', 'checked-in')->first();

        $response = $this->putJson('/api/bookings/'.$booking->id.'/check-out');
        $response->assertStatus(422);
    }

    public function test_if_checkout_works_with_valid_data(): void
    {
        $this->seed();

        $this->actingAsAdmin();

        $booking = \App\Models\Booking::where('status', '=', 'checked-in')->first();

        $response = $this->putJson('/api/bookings/'.$booking->id.'/check-out', []);
        $response->assertStatus(200);

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => 'checked-out',
        ]);
    }

    public function test_if_booking_stats_fails_in_unauthorized_user(): void
    {
        $this->seed();

        $user = \App\Models\User::factory()->create();

        $this->actingAs($user);

        $response = $this->getJson('/api/bookings/stats?days=7');

        $response->assertStatus(403);
    }

    public function test_if_booking_stats_works_for_admin(): void
    {
        $this->seed();

        $this->actingAsAdmin();

        $response = $this->getJson('/api/bookings/stats?last=7');

        $response->assertStatus(200);
        $response->assertJson(function (AssertableJson $json) {
            $json->whereType('data.numBookings', 'integer')
                ->whereType('data.sales', 'integer|double')
                ->whereType('data.occupancyRate', 'integer|double')
                ->whereType('data.confirmedStaysCount', 'integer')
                ->whereType('data.confirmedStays', 'array')
                ->whereType('data.confirmedStays.0.id', 'integer')
                ->whereType('data.confirmedStays.0.startDate', 'string')
                ->whereType('data.confirmedStays.0.endDate', 'string')
                ->whereType('data.confirmedStays.0.numNights', 'integer')
                ->whereType('data.confirmedStays.0.totalPrice', 'integer|double|null')
                ->whereType('data.confirmedStays.0.extrasPrice', 'integer|double|null')
                ->whereType('data.confirmedStays.0.status', 'string')
                ->whereType('data.confirmedStays.0.created_at', 'string')
                ->whereType('data.bookings', 'array')
                ->whereType('data.bookings.0.id', 'integer')
                ->whereType('data.bookings.0.startDate', 'string')
                ->whereType('data.bookings.0.endDate', 'string')
                ->whereType('data.bookings.0.numNights', 'integer')
                ->whereType('data.bookings.0.totalPrice', 'integer|double|null')
                ->whereType('data.bookings.0.extrasPrice', 'integer|double|null')
                ->whereType('data.bookings.0.status', 'string')
                ->whereType('data.bookings.0.created_at', 'string')
                ->etc();
        });
    }

    public function test_if_delete_booking_fails_if_unauthenticated(): void
    {
        $response = $this->deleteJson('/api/bookings/1');

        $response->assertStatus(401);
    }

    public function test_if_delete_booking_fails_in_unauthorized_user(): void
    {
        $this->seed();

        $user = \App\Models\User::factory()->create();

        $this->actingAs($user);

        $response = $this->deleteJson('/api/bookings/1');

        $response->assertStatus(403);
    }

    public function test_if_delete_booking_works_for_admin(): void
    {
        $this->seed();

        $this->actingAsAdmin();

        $booking = \App\Models\Booking::first();
        $response = $this->deleteJson('/api/bookings/'.$booking->id);
        $response->assertStatus(204);
        $response->assertNoContent();
        $this->assertDatabaseMissing('bookings', [
            'id' => $booking->id,
        ]);
    }

    public function test_if_today_activities_fails_for_unauthenticated(): void
    {
        $response = $this->getJson('/api/bookings/today-activity');

        $response->assertStatus(401);
    }

    public function test_if_today_activities_fails_in_unauthorized_user(): void
    {
        $this->seed();

        $user = \App\Models\User::factory()->create();

        $this->actingAs($user);

        $response = $this->getJson('/api/bookings/today-activity');

        $response->assertStatus(403);
    }

    public function test_if_today_activities_works_for_admin(): void
    {
        $this->seed();

        $this->actingAsAdmin();

        $response = $this->getJson('/api/bookings/today-activity?last=7');

        $response->assertStatus(200);
        $response->assertJson(function (AssertableJson $json) {
            $json->whereType('data', 'array')
                ->whereType('data.0.id', 'integer')
                ->whereType('data.0.startDate', 'string')
                ->whereType('data.0.endDate', 'string')
                ->whereType('data.0.numNights', 'integer')
                ->whereType('data.0.numGuests', 'integer')
                ->whereType('data.0.propertyPrice', 'integer|double|null')
                ->whereType('data.0.extrasPrice', 'integer|double|null')
                ->whereType('data.0.totalPrice', 'integer|double|null')
                ->whereType('data.0.status', 'string')
                ->whereType('data.0.isPaid', 'boolean')
                ->whereType('data.0.hasBreakfast', 'boolean')
                ->whereType('data.0.observations', 'string|null')
                ->whereType('data.0.guest', 'array')
                ->whereType('data.0.guest.id', 'integer')
                ->whereType('data.0.guest.name', 'string')
                ->whereType('data.0.guest.email', 'string')
                ->whereType('data.0.guest.countryFlag', 'string')
                ->whereType('data.0.property.id', 'integer')
                ->whereType('data.0.property.name', 'string')
                ->whereType('data.0.property.maxCapacity', 'integer')
                ->whereType('data.0.property.regularPrice', 'double')
                ->whereType('data.0.property.discount', 'integer|double|null')
                ->whereType('data.0.property.description', 'string')
                ->whereType('data.0.property.image', 'string|null')
                ->etc();
        });
    }

    public function test_if_creating_a_new_booking_fails_if_unauthenticated(): void
    {
        $response = $this->postJson('/api/bookings', []);

        $response->assertStatus(401);
    }

    public function test_if_creating_a_new_booking_fails_if_unauthorized(): void
    {
        $this->seed();

        $user = \App\Models\User::factory()->create();

        $this->actingAs($user);

        $response = $this->postJson('/api/bookings', []);

        $response->assertStatus(403);
    }

    public function test_if_creating_booking_fails_with_past_dates(): void
    {
        $this->seed();

        $this->actingAsAdmin();

        $response = $this->postJson('/api/bookings', [
            'startDate' => '2020-01-01',
            'endDate' => '2020-01-05',
            'numGuests' => 2,
            'guest_id' => 1,
            'property_id' => Property::factory()->create()->id,
        ]);

        $response->assertStatus(422);
    }

    public function test_if_creating_booking_fails_with_start_date_bigger_than_end(): void
    {
        $this->seed();

        $this->actingAsAdmin();

        $response = $this->postJson('/api/bookings', [
            'startDate' => now()->addDays(10)->format('Y-m-d'),
            'endDate' => now()->format('Y-m-d'),
            'numGuests' => 2,
            'guest_id' => 1,
            'property_id' => Property::factory()->create()->id,
        ]);

        $response->assertStatus(422);
    }

    public function test_if_creating_booking_fails_with_num_of_guests_bigger_than_configured(): void
    {
        $this->seed();

        $this->actingAsAdmin();

        $setting = \App\Models\Setting::first();
        $maxGuestsPerBooking = $setting->maxGuestsPerBooking;

        $startDate = now()->format('Y-m-d');
        $endDate = now()->addDay(5)->format('Y-m-d');

        $response = $this->postJson('/api/bookings', [
            'startDate' => $startDate,
            'endDate' => $endDate,
            'numGuests' => $maxGuestsPerBooking + 1,
            'guest_id' => Guest::factory()->create()->id,
            'property_id' => Property::factory()->create()->id,
        ]);

        $response->assertStatus(422);
    }

    public function test_if_creating_booking_fails_with_stay_small_than_configured(): void
    {
        $this->seed();

        $this->actingAsAdmin();

        $setting = \App\Models\Setting::first();
        $minBookingLength = $setting->minBookingLength;

        $startDate = now()->format('Y-m-d');
        $endDate = now()->addDay($minBookingLength - 1)->format('Y-m-d');

        $response = $this->postJson('/api/bookings', [
            'startDate' => $startDate,
            'endDate' => $endDate,
            'numGuests' => 2,
            'guest_id' => 1,
            'property_id' => Property::factory()->create()->id,
        ]);

        $response->assertStatus(422);
    }

    public function test_if_creating_booking_fails_with_stay_longer_than_configured(): void
    {
        $this->seed();

        $this->actingAsAdmin();

        $setting = \App\Models\Setting::first();
        $maxBookingLength = $setting->maxBookingLength;

        $startDate = now()->format('Y-m-d');
        $endDate = now()->addDay($maxBookingLength + 1)->format('Y-m-d');

        $response = $this->postJson('/api/bookings', [
            'startDate' => $startDate,
            'endDate' => $endDate,
            'numGuests' => 2,
            'guest_id' => 1,
            'property_id' => Property::factory()->create()->id,
        ]);

        $response->assertStatus(422);
    }

    public function test_if_creating_booking_fails_with_already_booked_date(): void
    {
        $this->seed();

        $this->actingAsAdmin();

        $propertyId = Property::factory()->create()->id;

        $response = $this->postJson('/api/bookings', [
            'startDate' => now()->addDays(1)->format('Y-m-d'),
            'endDate' => now()->addDays(5)->format('Y-m-d'),
            'numGuests' => 2,
            'guest_id' => 1,
            'property_id' => $propertyId,
        ]);

        $response->assertStatus(201);

        $response = $this->postJson('/api/bookings', [
            'startDate' => now()->addDays(1)->format('Y-m-d'),
            'endDate' => now()->addDays(5)->format('Y-m-d'),
            'numGuests' => 2,
            'guest_id' => 1,
            'property_id' => $propertyId,
        ]);
        $response->assertStatus(422);
    }

    public function test_if_creating_booking_works(): void
    {
        $this->seed();

        $this->actingAsAdmin();

        $response = $this->postJson('/api/bookings', [
            'startDate' => now()->addDays(1)->format('Y-m-d'),
            'endDate' => now()->addDays(5)->format('Y-m-d'),
            'numGuests' => 2,
            'guest_id' => 1,
            'property_id' => Property::factory()->create()->id,
        ]);

        $response->assertStatus(201);

        $response->assertJson(function (AssertableJson $json) {
            $json
                ->has('success')
                ->has('message')
                ->whereType('data.id', 'integer')
                ->whereType('data.startDate', 'string')
                ->whereType('data.endDate', 'string')
                ->whereType('data.numNights', 'integer')
                ->whereType('data.numGuests', 'integer')
                ->whereType('data.propertyPrice', 'integer|double')
                ->whereType('data.extrasPrice', 'integer|double|null')
                ->whereType('data.totalPrice', 'integer|double')
                ->whereType('data.status', 'string')
                ->whereType('data.isPaid', 'boolean')
                ->whereType('data.hasBreakfast', 'boolean')
                ->whereType('data.observations', 'string|null')
                ->whereType('data.guest', 'array')
                ->whereType('data.guest.id', 'integer')
                ->whereType('data.guest.name', 'string')
                ->whereType('data.guest.email', 'string')
                ->whereType('data.guest.countryFlag', 'string')
                ->whereType('data.property.id', 'integer')
                ->whereType('data.property.name', 'string')
                ->whereType('data.property.maxCapacity', 'integer')
                ->whereType('data.property.regularPrice', 'double')
                ->whereType('data.property.discount', 'integer|double|null')
                ->whereType('data.property.description', 'string')
                ->whereType('data.property.image', 'string|null')
                ->etc();
        });

    }
}
