<?php

namespace Tests\Feature\Models;

use App\Models\Booking;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

class BookingTest extends TestCase
{
    use RefreshDatabase;

    public function testIfBookingHasGuest(): void
    {
        $this->seed();

        $booking = \App\Models\Booking::first();

        $this->assertTrue(method_exists($booking, 'guest'));
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $booking->guest());
        $this->assertNotNull($booking->guest);
    }

    public function testIfBookingHasProperty(): void
    {
        $this->seed();

        $booking = \App\Models\Booking::first();

        $this->assertTrue(method_exists($booking, 'property'));
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $booking->property());
        $this->assertNotNull($booking->property);
    }

    public function testifBookingsCanBeListedWithAuthorizedUser(): void
    {
        $this->seed();

        $user = \App\Models\User::first();

        $this->actingAs($user);

        $bookings = (new Booking())->list();

        $this->assertInstanceOf(LengthAwarePaginator::class, $bookings);
        $this->assertGreaterThan(0, $bookings->total());
        $this->assertCount(10, $bookings->items());
        $this->assertEquals(1, $bookings->currentPage());
        $this->assertGreaterThan(1, $bookings->lastPage());

        \Illuminate\Pagination\Paginator::currentPageResolver(function () {
            return 2;
        });

        $bookingsPage2 = (new Booking())->list();
        $this->assertInstanceOf(LengthAwarePaginator::class, $bookingsPage2);
        $this->assertEquals(2, $bookingsPage2->currentPage());

        $bookings = (new Booking())->list('id', 'desc');
        $this->assertGreaterThanOrEqual($bookings->items()[1]->id, $bookings->items()[0]->id);

        $bookings = (new Booking())->list('id', 'desc', 'checked-out');
        $bookingsNotCheckedOut = array_filter($bookings->items(), function ($booking) {
            return $booking->status !== 'checked-out';
        });
        $this->assertNotEquals(count($bookings->items()), count($bookingsNotCheckedOut));

        $bookings = (new Booking())->list('id', 'desc', 'checked-in');
        $bookingsNotCheckedIn = array_filter($bookings->items(), function ($booking) {
            return $booking->status !== 'checked-in';
        });
        $this->assertNotEquals(count($bookings->items()), count($bookingsNotCheckedIn));

        $bookings = (new Booking())->list('id', 'desc', 'unconfirmed');
        $bookingsDifferentThanUnconfirmed = array_filter($bookings->items(), function ($booking) {
            return $booking->status !== 'unconfirmed';
        });
        $this->assertNotEquals(count($bookings->items()), count($bookingsDifferentThanUnconfirmed));
    }

    public function testIfBookingsCannotBeListedWithUnauthorizedUser(): void
    {
        $this->seed();

        $user = User::factory(1)->create()->first();

        $this->actingAs($user);

        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);
        (new Booking())->list();
    }

    public function testIfCanGetBookingDetails(): void
    {
        $this->seed();

        $booking = \App\Models\Booking::first();

        $bookingDetails = (new Booking())->details($booking->id);

        $this->assertNotNull($bookingDetails);
        $this->assertEquals($booking->id, $bookingDetails->id);
    }

    public function testIfCanDoChecking()
    {
        $this->seed();

        $user = User::getMasterAdmin();;

        $this->actingAs($user);

        $booking = Booking::factory(1)->create([
            'hasBreakfast' => false,
            'status' => 'unconfirmed',
            'isPaid' => false,
        ])->first();

        $booking->checkIn($booking->id);

        $bookingAfterCheckIn = \App\Models\Booking::find($booking->id);

        $settings = (new Setting())->initializeSettings();

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

    public function testIfCannotDoCheckingWithUnauthorizedUser()
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

    public function testIfCannotDoCheckingInDifferentStatusThanUnconfirmed()
    {
        $this->seed();

        $user = User::getMasterAdmin();;

        $this->actingAs($user);

        $booking = Booking::factory(1)->create([
            'hasBreakfast' => false,
            'status' => 'checked-in',
            'isPaid' => false,
        ])->first();

        $this->expectException(\App\Exceptions\BaseException::class);
        $booking->checkIn($booking->id);
    }

    public function testIfCanDoCheckOut()
    {
        $this->seed();

        $user = User::getMasterAdmin();;

        $this->actingAs($user);

        $booking = Booking::factory(1)->create([
            'status' => 'checked-in',
        ])->first();

        $booking->checkOut($booking->id);

        $bookingAfterCheckIn = \App\Models\Booking::find($booking->id);


        $this->assertEquals('checked-out', $bookingAfterCheckIn->status);
    }

    public function testIfCannotDoCheckOutInDifferentStatusThanCheckedIn()
    {
        $this->seed();

        $user = User::getMasterAdmin();;

        $this->actingAs($user);

        $booking = Booking::factory(1)->create([
            'status' => 'unconfirmed',
        ])->first();

        $this->expectException(\App\Exceptions\BaseException::class);
        $booking->checkOut($booking->id);
    }

    public function testIfCanGetStatsFromBookings(): void
    {

        $this->seed();

        $user = User::getMasterAdmin();;

        $this->actingAs($user);

        $stats = (new Booking())->stats(7);

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

    public function testIfCanGetTodayActivitiesFromBookings(): void
    {
        $this->seed();

        $user = User::getMasterAdmin();;

        $this->actingAs($user);

        $activities = (new Booking())->todayActivities();

        $this->assertIsIterable($activities);

        $firstActivity = $activities->first();

        $this->assertInstanceOf(Booking::class, $firstActivity);
        $this->assertNotNull($firstActivity->guest);
        $this->assertNotNull($firstActivity->guest->user);
        $this->assertNotNull($firstActivity->property);
    }

    public function testIfCanDeleteBooking(): void
    {
        $this->seed();

        $user = User::getMasterAdmin();;

        $this->actingAs($user);

        $booking = Booking::factory(1)->create()->first();

        $booking->deleteBooking();

        $this->assertDatabaseMissing('bookings', [
            'id' => $booking->id,
        ]);
    }

    public function testIfUnauthorizedUserCannotDeleteBooking(): void
    {
        $this->seed();

        $user = User::factory(1)->create()->first();

        $this->actingAs($user);

        $booking = Booking::factory(1)->create()->first();

        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);
        $booking->deleteBooking();
    }
}
