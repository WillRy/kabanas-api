<?php

namespace Tests\Feature\Controllers\Api;


use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class BookingControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    public function testIfListBookingsFailsIfUnauthenticated(): void
    {
        $response = $this->getJson('/api/bookings');

        $response->assertStatus(401);
    }

    public function testIfListBookingsFailsInUnauthorizedUser(): void
    {
        $this->seed();

        $user = \App\Models\User::factory()->create();

        $this->actingAs($user);

        $response = $this->getJson('/api/bookings');

        $response->assertStatus(403);
    }


    public function testIfListBookingsWorksForAdmin(): void
    {
        $this->seed();

        $this->actingAsAdmin();

        $response = $this->getJson('/api/bookings');
        $response->assertStatus(200);
        $response->assertJson(function(AssertableJson $json) {
            $json
                ->has('success')
                ->has('message')
                ->whereType('data', 'array')
                ->whereType('data.data', 'array')
                ->whereType('data.data.0.id', 'integer')
                ->whereType('data.data.0.startDate', 'string')
                ->whereType('data.data.0.endDate', 'string')
                ->whereType('data.data.0.numNights', 'integer')
                ->whereType('data.data.0.numGuests', 'integer')
                ->whereType('data.data.0.propertyPrice', "integer|double")
                ->whereType('data.data.0.extrasPrice', "integer|double|null")
                ->whereType('data.data.0.totalPrice', "integer|double")
                ->whereType('data.data.0.status', 'string')
                ->whereType('data.data.0.isPaid', 'boolean')
                ->whereType('data.data.0.hasBreakfast', 'boolean')
                ->whereType('data.data.0.observations', "string|null")
                ->whereType('data.data.0.guest', 'array')
                ->whereType('data.data.0.guest.id', 'integer')
                ->whereType('data.data.0.guest.name', 'string')
                ->whereType('data.data.0.guest.email', 'string')
                ->whereType('data.data.0.guest.countryFlag', 'string')
                ->whereType('data.data.0.property.id', 'integer')
                ->whereType('data.data.0.property.name', 'string')
                ->whereType('data.data.0.property.maxCapacity', 'integer')
                ->whereType('data.data.0.property.regularPrice', "integer|double")
                ->whereType('data.data.0.property.discount', "integer|double|null")
                ->whereType('data.data.0.property.description', 'string')
                ->whereType('data.data.0.property.image', "string|null")
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

    public function testIfCannotViewBookingWithUnauthorizedUser(): void
    {
        $this->seed();

        $user = \App\Models\User::factory()->create();

        $this->actingAs($user);

        $booking = \App\Models\Booking::first();

        $response = $this->getJson('/api/bookings/' . $booking->id);

        $response->assertStatus(403);
    }


    public function testIfViewBookingFailsWithInexistentBooking(): void
    {
        $this->seed();

        $user = \App\Models\User::factory()->create();

        $this->actingAs($user);

        $response = $this->getJson('/api/bookings/9999');
        $response->assertStatus(404);
    }

    public function testIfViewBookingWorksWithExistentBooking(): void
    {
        $this->seed();

        $this->actingAsAdmin();

        $booking = \App\Models\Booking::first();

        $response = $this->getJson('/api/bookings/' . $booking->id);

        $response->assertStatus(200);
        $response->assertJson(function(AssertableJson $json) {
            $json
                ->has('success')
                ->has('message')
                ->whereType('data.id', 'integer')
                ->whereType('data.startDate', 'string')
                ->whereType('data.endDate', 'string')
                ->whereType('data.numNights', 'integer')
                ->whereType('data.numGuests', 'integer')
                ->whereType('data.propertyPrice', "integer|double")
                ->whereType('data.extrasPrice', "integer|double|null")
                ->whereType('data.totalPrice', "integer|double")
                ->whereType('data.status', 'string')
                ->whereType('data.isPaid', 'boolean')
                ->whereType('data.hasBreakfast', 'boolean')
                ->whereType('data.observations', "string|null")
                ->whereType('data.guest', 'array')
                ->whereType('data.guest.id', 'integer')
                ->whereType('data.guest.name', 'string')
                ->whereType('data.guest.email', 'string')
                ->whereType('data.guest.countryFlag', 'string')
                ->whereType('data.property.id', 'integer')
                ->whereType('data.property.name', 'string')
                ->whereType('data.property.maxCapacity', 'integer')
                ->whereType('data.property.regularPrice', 'double')
                ->whereType('data.property.discount', "integer|double|null")
                ->whereType('data.property.description', 'string')
                ->whereType('data.property.image', "string|null")
                ->etc();
        });
    }

    public function testIfCheckinFailsIfUnauthenticated(): void
    {
        $response = $this->putJson('/api/bookings/1/check-in');

        $response->assertStatus(401);
    }

    public function testIfCheckinFailsInUnauthorizedUser(): void
    {
        $this->seed();

        $user = \App\Models\User::factory()->create();

        $this->actingAs($user);

        $response = $this->putJson('/api/bookings/1/check-in');

        $response->assertStatus(403);
    }

    public function testIfCheckinFailsWithDifferentStatusThanUnconfirmed(): void
    {
        $this->seed();

        $this->actingAsAdmin();

        $booking = \App\Models\Booking::where('status', '!=', 'unconfirmed')->first();

        $response = $this->putJson('/api/bookings/' . $booking->id . '/check-in');
        $response->assertStatus(422);
    }

    public function testIfCheckinFailsWithInexistentBooking(): void
    {
        $this->seed();

        $this->actingAsAdmin();


        $response = $this->putJson('/api/bookings/9999999999/check-in');
        $response->assertStatus(404);
    }

    public function testIfCheckinWorksWithValidData(): void
    {
        $this->seed();

        $this->actingAsAdmin();

        $booking = \App\Models\Booking::where('status', '=', 'unconfirmed')->first();

        $response = $this->putJson('/api/bookings/' . $booking->id . '/check-in');
        $response->assertStatus(200);

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => 'checked-in',
        ]);
    }

    public function testIfCheckoutFailsIfUnauthenticated(): void
    {
        $response = $this->putJson('/api/bookings/1/check-out');

        $response->assertStatus(401);
    }

    public function testIfCheckoutFailsInUnauthorizedUser(): void
    {
        $this->seed();

        $user = \App\Models\User::factory()->create();

        $this->actingAs($user);

        $response = $this->putJson('/api/bookings/1/check-out');

        $response->assertStatus(403);
    }

    public function testIfCheckoutFailsWithDifferentStatusThanCheckedIn(): void
    {
        $this->seed();

        $this->actingAsAdmin();

        $booking = \App\Models\Booking::where('status', '!=', 'checked-in')->first();

        $response = $this->putJson('/api/bookings/' . $booking->id . '/check-out');
        $response->assertStatus(422);
    }

    public function testIfCheckoutWorksWithValidData(): void
    {
        $this->seed();

        $this->actingAsAdmin();

        $booking = \App\Models\Booking::where('status', '=', 'checked-in')->first();

        $response = $this->putJson('/api/bookings/' . $booking->id . '/check-out', []);
        $response->assertStatus(200);

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => 'checked-out',
        ]);
    }

    public function testIfBookingStatsFailsInUnauthorizedUser(): void
    {
        $this->seed();

        $user = \App\Models\User::factory()->create();

        $this->actingAs($user);

        $response = $this->getJson('/api/bookings/stats?days=7');

        $response->assertStatus(403);
    }

    public function testIfBookingStatsWorksForAdmin(): void
    {
        $this->seed();

        $this->actingAsAdmin();

        $response = $this->getJson('/api/bookings/stats?last=7');

        $response->assertStatus(200);
        $response->assertJson(function (AssertableJson $json) {
            $json->whereType('data.numBookings', 'integer')
                ->whereType('data.sales', "integer|double")
                ->whereType('data.occupancyRate', "integer|double")
                ->whereType('data.confirmedStaysCount', 'integer')
                ->whereType('data.confirmedStays', 'array')
                ->whereType('data.confirmedStays.0.id', 'integer')
                ->whereType('data.confirmedStays.0.startDate', 'string')
                ->whereType('data.confirmedStays.0.endDate', 'string')
                ->whereType('data.confirmedStays.0.numNights', 'integer')
                ->whereType('data.confirmedStays.0.totalPrice', "integer|double|null")
                ->whereType('data.confirmedStays.0.extrasPrice', "integer|double|null")
                ->whereType('data.confirmedStays.0.status', 'string')
                ->whereType('data.confirmedStays.0.created_at', 'string')
                ->whereType('data.bookings', 'array')
                ->whereType('data.bookings.0.id', 'integer')
                ->whereType('data.bookings.0.startDate', 'string')
                ->whereType('data.bookings.0.endDate', 'string')
                ->whereType('data.bookings.0.numNights', 'integer')
                ->whereType('data.bookings.0.totalPrice', "integer|double|null")
                ->whereType('data.bookings.0.extrasPrice', "integer|double|null")
                ->whereType('data.bookings.0.status', 'string')
                ->whereType('data.bookings.0.created_at', 'string')
                ->etc();
        });
    }

    public function testIfDeleteBookingFailsIfUnauthenticated(): void
    {
        $response = $this->deleteJson('/api/bookings/1');

        $response->assertStatus(401);
    }

    public function testIfDeleteBookingFailsInUnauthorizedUser(): void
    {
        $this->seed();

        $user = \App\Models\User::factory()->create();

        $this->actingAs($user);

        $response = $this->deleteJson('/api/bookings/1');

        $response->assertStatus(403);
    }

    public function testIfDeleteBookingWorksForAdmin(): void
    {
        $this->seed();

        $this->actingAsAdmin();

        $booking = \App\Models\Booking::first();
        $response = $this->deleteJson('/api/bookings/' . $booking->id);
        $response->assertStatus(204);
        $response->assertNoContent();
        $this->assertDatabaseMissing('bookings', [
            'id' => $booking->id,
        ]);
    }

    public function testIfTodayActivitiesFailsForUnauthenticated(): void
    {
        $response = $this->getJson('/api/bookings/today-activity');

        $response->assertStatus(401);
    }

    public function testIfTodayActivitiesFailsInUnauthorizedUser(): void
    {
        $this->seed();

        $user = \App\Models\User::factory()->create();

        $this->actingAs($user);

        $response = $this->getJson('/api/bookings/today-activity');

        $response->assertStatus(403);
    }

    public function testIfTodayActivitiesWorksForAdmin(): void
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
                ->whereType('data.0.propertyPrice', "integer|double|null")
                ->whereType('data.0.extrasPrice', "integer|double|null")
                ->whereType('data.0.totalPrice', "integer|double|null")
                ->whereType('data.0.status', 'string')
                ->whereType('data.0.isPaid', 'boolean')
                ->whereType('data.0.hasBreakfast', 'boolean')
                ->whereType('data.0.observations', "string|null")
                ->whereType('data.0.guest', 'array')
                ->whereType('data.0.guest.id', 'integer')
                ->whereType('data.0.guest.name', 'string')
                ->whereType('data.0.guest.email', 'string')
                ->whereType('data.0.guest.countryFlag', 'string')
                ->whereType('data.0.property.id', 'integer')
                ->whereType('data.0.property.name', 'string')
                ->whereType('data.0.property.maxCapacity', 'integer')
                ->whereType('data.0.property.regularPrice', 'double')
                ->whereType('data.0.property.discount', "integer|double|null")
                ->whereType('data.0.property.description', 'string')
                ->whereType('data.0.property.image', "string|null")
                ->etc();
        });
    }
}
