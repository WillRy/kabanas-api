<?php

namespace Tests\Feature\Controllers\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
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

        $user = User::getMasterAdmin();

        $this->actingAs($user);

        $response = $this->getJson('/api/bookings');
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'data' => [
                    '*' => [
                        'id',
                        'startDate',
                        'endDate',
                        'numNights',
                        'numGuests',
                        'propertyPrice',
                        'extrasPrice',
                        'totalPrice',
                        'status',
                        'hasBreakfast',
                        'isPaid',
                        'observations',
                        'guest' => [
                            'id',
                            'name',
                            'email',
                            'countryFlag',
                        ],
                        'property' => [
                            'id',
                            'name',
                            'maxCapacity',
                            'regularPrice',
                            'discount',
                            'description',
                            'image',
                            'created_at',
                            'updated_at',
                        ],
                        'created_at',
                        'updated_at',
                    ],
                ],
            ],
        ]);


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

        $user = User::getMasterAdmin();

        $this->actingAs($user);

        $booking = \App\Models\Booking::first();

        $response = $this->getJson('/api/bookings/' . $booking->id);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            "data" => [
                'id',
                'startDate',
                'endDate',
                'numNights',
                'numGuests',
                'propertyPrice',
                'extrasPrice',
                'totalPrice',
                'status',
                'hasBreakfast',
                'isPaid',
                'observations',
                'guest' => [
                    'id',
                    'name',
                    'email',
                    'countryFlag',
                ],
                'property' => [
                    'id',
                    'name',
                    'maxCapacity',
                    'regularPrice',
                    'discount',
                    'description',
                    'image',
                    'created_at',
                    'updated_at',
                ],
                'created_at',
                'updated_at',
            ]
        ]);
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

        $user = $user = User::getMasterAdmin();

        $this->actingAs($user);

        $booking = \App\Models\Booking::where('status', '!=', 'unconfirmed')->first();

        $response = $this->putJson('/api/bookings/' . $booking->id . '/check-in');
        $response->assertStatus(422);
    }

    public function testIfCheckinFailsWithInexistentBooking(): void
    {
        $this->seed();

        $user = User::getMasterAdmin();

        $this->actingAs($user);


        $response = $this->putJson('/api/bookings/9999999999/check-in');
        $response->assertStatus(404);
    }

    public function testIfCheckinWorksWithValidData(): void
    {
        $this->seed();

        $user = User::getMasterAdmin();

        $this->actingAs($user);

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

        $user = User::getMasterAdmin();

        $this->actingAs($user);

        $booking = \App\Models\Booking::where('status', '!=', 'checked-in')->first();

        $response = $this->putJson('/api/bookings/' . $booking->id . '/check-out');
        $response->assertStatus(422);
    }

    public function testIfCheckoutWorksWithValidData(): void
    {
        $this->seed();

        $user = User::getMasterAdmin();

        $this->actingAs($user);

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

        $user = User::getMasterAdmin();

        $this->actingAs($user);

        $response = $this->getJson('/api/bookings/stats?last=7');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'numBookings',
                'sales',
                'occupancyRate',
                'confirmedStaysCount',
                'confirmedStays' => [
                    '*' => [
                        'id',
                        'startDate',
                        'endDate',
                        'numNights',
                        'totalPrice',
                        'extrasPrice',
                        'status',
                        'created_at'
                    ],
                ],
                'bookings' => [
                    "*" => [
                        'id',
                        'startDate',
                        'endDate',
                        'numNights',
                        'totalPrice',
                        'extrasPrice',
                        'status',
                        'created_at'
                    ],
                ],
            ],
        ]);
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

        $user = User::getMasterAdmin();

        $this->actingAs($user);

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

        $user = User::getMasterAdmin();

        $this->actingAs($user);

        $response = $this->getJson('/api/bookings/today-activity?last=7');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            "data" => [
                "*" => [
                    'id',
                    'startDate',
                    'endDate',
                    'numNights',
                    'numGuests',
                    'propertyPrice',
                    'extrasPrice',
                    'totalPrice',
                    'status',
                    'hasBreakfast',
                    'isPaid',
                    'observations',
                    'guest' => [
                        'id',
                        'name',
                        'email',
                        'countryFlag',
                    ],
                    'property' => [
                        'id',
                        'name',
                        'maxCapacity',
                        'regularPrice',
                        'discount',
                        'description',
                        'image',
                        'created_at',
                        'updated_at',
                    ],
                    'created_at',
                    'updated_at',
                ]
            ]
        ]);
    }
}
