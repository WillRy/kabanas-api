<?php

namespace Tests\Feature\Models;

use App\Exceptions\BaseException;
use App\Models\Booking;
use App\Models\Guest;
use App\Models\Property;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

class BookingTest extends TestCase
{
    use RefreshDatabase;

    public function test_if_booking_has_guest(): void
    {
        $this->seed();

        $booking = \App\Models\Booking::first();

        $this->assertTrue(method_exists($booking, 'guest'));
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $booking->guest());
        $this->assertNotNull($booking->guest);
    }

    public function test_if_booking_has_property(): void
    {
        $this->seed();

        $booking = \App\Models\Booking::first();

        $this->assertTrue(method_exists($booking, 'property'));
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $booking->property());
        $this->assertNotNull($booking->property);
    }

    public function testif_bookings_can_be_listed_with_authorized_user(): void
    {
        $this->seed();

        $user = \App\Models\User::first();

        $this->actingAs($user);

        $bookings = (new Booking)->list();

        $this->assertInstanceOf(LengthAwarePaginator::class, $bookings);
        $this->assertGreaterThan(0, $bookings->total());
        $this->assertCount(10, $bookings->items());
        $this->assertEquals(1, $bookings->currentPage());
        $this->assertGreaterThan(1, $bookings->lastPage());

        \Illuminate\Pagination\Paginator::currentPageResolver(function () {
            return 2;
        });

        $bookingsPage2 = (new Booking)->list();
        $this->assertInstanceOf(LengthAwarePaginator::class, $bookingsPage2);
        $this->assertEquals(2, $bookingsPage2->currentPage());

        $bookings = (new Booking)->list('id', 'desc');
        $this->assertGreaterThanOrEqual($bookings->items()[1]->id, $bookings->items()[0]->id);

        $bookings = (new Booking)->list('id', 'desc', 'checked-out');
        $bookingsNotCheckedOut = array_filter($bookings->items(), function ($booking) {
            return $booking->status !== 'checked-out';
        });
        $this->assertNotEquals(count($bookings->items()), count($bookingsNotCheckedOut));

        $bookings = (new Booking)->list('id', 'desc', 'checked-in');
        $bookingsNotCheckedIn = array_filter($bookings->items(), function ($booking) {
            return $booking->status !== 'checked-in';
        });
        $this->assertNotEquals(count($bookings->items()), count($bookingsNotCheckedIn));

        $bookings = (new Booking)->list('id', 'desc', 'unconfirmed');
        $bookingsDifferentThanUnconfirmed = array_filter($bookings->items(), function ($booking) {
            return $booking->status !== 'unconfirmed';
        });
        $this->assertNotEquals(count($bookings->items()), count($bookingsDifferentThanUnconfirmed));
    }

    public function test_if_bookings_cannot_be_listed_with_unauthorized_user(): void
    {
        $this->seed();

        $user = User::factory(1)->create()->first();

        $this->actingAs($user);

        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);
        (new Booking)->list();
    }

    public function test_if_can_get_booking_details(): void
    {
        $this->seed();

        $booking = \App\Models\Booking::first();

        $bookingDetails = (new Booking)->details($booking->id);

        $this->assertNotNull($bookingDetails);
        $this->assertEquals($booking->id, $bookingDetails->id);
    }

    public function test_if_can_do_checking()
    {
        $this->seed();

        $this->actingAsAdmin();

        $booking = Booking::factory(1)->create([
            'hasBreakfast' => false,
            'status' => 'unconfirmed',
            'isPaid' => false,
        ])->first();

        $booking->checkIn($booking->id);

        $bookingAfterCheckIn = \App\Models\Booking::find($booking->id);

        $settings = (new Setting)->initializeSettings();

        $extrasPrice = 0;
        $totalPrice = round(($booking->propertyPrice * $booking->numNights) + $extrasPrice, 2);

        $this->assertEquals('checked-in', $bookingAfterCheckIn->status);
        $this->assertEquals(true, $bookingAfterCheckIn->isPaid);
        $this->assertEquals($totalPrice, $bookingAfterCheckIn->totalPrice);
        $this->assertEquals($extrasPrice, $bookingAfterCheckIn->extrasPrice);

        $booking = Booking::factory(1)->create([
            'hasBreakfast' => true,
            'status' => 'unconfirmed',
            'isPaid' => false,
        ])->first();

        $booking->checkIn($booking->id);

        $bookingAfterCheckIn = \App\Models\Booking::find($booking->id);

        $extrasPrice = round($settings->breakfastPrice * $booking->numNights * $booking->numGuests, 2);
        $totalPrice = round(($booking->propertyPrice * $booking->numNights) + $extrasPrice, 2);

        $this->assertEquals('checked-in', $bookingAfterCheckIn->status);
        $this->assertEquals(true, $bookingAfterCheckIn->isPaid);
        $this->assertEquals($totalPrice, $bookingAfterCheckIn->totalPrice);
        $this->assertEquals($extrasPrice, $bookingAfterCheckIn->extrasPrice);
    }

    public function test_if_cannot_do_checking_with_unauthorized_user()
    {
        $this->seed();

        $user = User::factory(1)->create()->first();

        $this->actingAs($user);

        $booking = Booking::factory(1)->create([
            'hasBreakfast' => false,
            'status' => 'unconfirmed',
            'isPaid' => false,
        ])->first();

        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);
        $booking->checkIn($booking->id);
    }

    public function test_if_cannot_do_checking_in_different_status_than_unconfirmed()
    {
        $this->seed();

        $this->actingAsAdmin();

        $booking = Booking::factory(1)->create([
            'hasBreakfast' => false,
            'status' => 'checked-in',
            'isPaid' => false,
        ])->first();

        $this->expectException(\App\Exceptions\BaseException::class);
        $booking->checkIn($booking->id);
    }

    public function test_if_can_do_check_out()
    {
        $this->seed();

        $this->actingAsAdmin();

        $booking = Booking::factory(1)->create([
            'status' => 'checked-in',
        ])->first();

        $booking->checkOut($booking->id);

        $bookingAfterCheckIn = \App\Models\Booking::find($booking->id);

        $this->assertEquals('checked-out', $bookingAfterCheckIn->status);
    }

    public function test_if_cannot_do_check_out_in_different_status_than_checked_in()
    {
        $this->seed();

        $this->actingAsAdmin();

        $booking = Booking::factory(1)->create([
            'status' => 'unconfirmed',
        ])->first();

        $this->expectException(\App\Exceptions\BaseException::class);
        $booking->checkOut($booking->id);
    }

    public function test_if_can_get_stats_from_bookings(): void
    {

        $this->seed();

        $this->actingAsAdmin();

        $stats = (new Booking)->stats(7);

        $this->assertIsArray($stats);
        $this->assertEqualsCanonicalizing([
            'numBookings',
            'sales',
            'occupancyRate',
            'confirmedStaysCount',
            'confirmedStays',
            'bookings',
        ], array_keys($stats));
        $this->assertGreaterThanOrEqual(0, $stats['numBookings']);
        $this->assertGreaterThanOrEqual(0, $stats['sales']);
        $this->assertGreaterThanOrEqual(0, $stats['occupancyRate']);
        $this->assertGreaterThanOrEqual(0, $stats['confirmedStaysCount']);
        $this->assertIsIterable($stats['confirmedStays']);
        $this->assertIsIterable($stats['bookings']);
    }

    public function test_if_can_get_today_activities_from_bookings(): void
    {
        $this->seed();

        $this->actingAsAdmin();

        $activities = (new Booking)->todayActivities();

        $this->assertIsIterable($activities);

        $firstActivity = $activities->first();

        $this->assertInstanceOf(Booking::class, $firstActivity);
        $this->assertNotNull($firstActivity->guest);
        $this->assertNotNull($firstActivity->guest->user);
        $this->assertNotNull($firstActivity->property);
    }

    public function test_if_can_delete_booking(): void
    {
        $this->seed();

        $this->actingAsAdmin();

        $booking = Booking::factory(1)->create()->first();

        $booking->deleteBooking();

        $this->assertDatabaseMissing('bookings', [
            'id' => $booking->id,
        ]);
    }

    public function test_if_unauthorized_user_cannot_delete_booking(): void
    {
        $this->seed();

        $user = User::factory(1)->create()->first();

        $this->actingAs($user);

        $booking = Booking::factory(1)->create()->first();

        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);
        $booking->deleteBooking();
    }

    public function test_if_creating_booking_if_inexistent_property_throws_exception(): void
    {
        $this->seed();

        $this->actingAsAdmin();

        $data = [
            'startDate' => now()->format('Y-m-d'),
            'endDate' => now()->addDays(5)->format('Y-m-d'),
            'observations' => 'test',
            'numGuests' => 2,
            'guest_id' => 1,
            'property_id' => 9999,
        ];

        $this->expectException(BaseException::class);
        $this->expectExceptionCode(404);
        (new Booking)->createBooking($data);
    }

    public function test_if_creating_booking_fails_with_past_dates(): void
    {
        $this->seed();

        $this->actingAsAdmin();

        $data = [
            'startDate' => now()->subDays(4)->format('Y-m-d'),
            'endDate' => now()->subDays(2)->format('Y-m-d'),
            'observations' => 'test',
            'numGuests' => 2,
            'guest_id' => Guest::factory()->create()->id,
            'property_id' => Property::factory()->create()->id,
        ];

        $this->expectException(BaseException::class);
        $this->expectExceptionCode(422);
        (new Booking)->createBooking($data);
    }

    public function test_if_creating_booking_fails_with_stay_small_than_configured(): void
    {
        $this->seed();

        $this->actingAsAdmin();

        $setting = \App\Models\Setting::first();
        $minBookingLength = $setting->minBookingLength;

        $startDate = now()->format('Y-m-d');
        $endDate = now()->addDay($minBookingLength - 1)->format('Y-m-d');

        $data = [
            'startDate' => $startDate,
            'endDate' => $endDate,
            'observations' => 'test',
            'numGuests' => 2,
            'guest_id' => Guest::factory()->create()->id,
            'property_id' => Property::factory()->create()->id,
        ];

        $this->expectException(BaseException::class);
        $this->expectExceptionCode(422);
        (new Booking)->createBooking($data);
    }

    public function test_if_creating_booking_fails_with_start_date_bigger_than_end(): void
    {
        $this->seed();

        $this->actingAsAdmin();

        $data = [
            'startDate' => now()->addDays(5)->format('Y-m-d'),
            'endDate' => now()->format('Y-m-d'),
            'observations' => 'test',
            'numGuests' => 2,
            'guest_id' => 1,
            'property_id' => 1,
        ];

        $this->expectException(BaseException::class);
        $this->expectExceptionCode(422);
        (new Booking)->createBooking($data);
    }

    public function test_if_creating_booking_if_already_booked_dates_throws_exception(): void
    {
        $this->seed();

        $this->actingAsAdmin();

        $data = [
            'startDate' => now()->format('Y-m-d'),
            'endDate' => now()->addDays(5)->format('Y-m-d'),
            'observations' => 'test',
            'numGuests' => 2,
            'guest_id' => 1,
            'property_id' => Property::factory()->create()->id,
        ];

        (new Booking)->createBooking($data);

        $this->expectException(BaseException::class);
        $this->expectExceptionCode(422);
        (new Booking)->createBooking($data);
    }

    public function test_if_can_create_booking(): void
    {
        $this->seed();

        $this->actingAsAdmin();

        $data = [
            'startDate' => now()->format('Y-m-d'),
            'endDate' => now()->addDays(5)->format('Y-m-d'),
            'observations' => 'test',
            'numGuests' => 2,
            'guest_id' => Guest::factory()->create()->id,
            'property_id' => Property::factory()->create()->id,
        ];

        (new Booking)->createBooking($data);

        $this->assertDatabaseHas('bookings', [
            'startDate' => "{$data['startDate']} 00:00:00",
            'endDate' => "{$data['endDate']} 00:00:00",
            'observations' => 'test',
            'numGuests' => 2,
            'guest_id' => $data['guest_id'],
            'property_id' => $data['property_id'],
            'status' => 'unconfirmed',
        ]);
    }
}
