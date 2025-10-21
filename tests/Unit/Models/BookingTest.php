<?php

namespace Tests\Unit\Models;

use App\Models\Booking;
use PHPUnit\Framework\TestCase;

class BookingTest extends TestCase
{
    public function testIfModelHasExpectedFillables()
    {
        $booking = new Booking();
        $expected = [
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
            'guest_id',
            'property_id',
        ];

        $this->assertEqualsCanonicalizing($expected, $booking->getFillable());
    }

    public function testIfModelHasExpectedCasts()
    {
        $booking = new Booking();
        $expected = [
            'startDate' => 'datetime',
            'endDate' => 'datetime',
            'propertyPrice' => 'float',
            'extrasPrice' => 'float',
            'totalPrice' => 'float',
            'hasBreakfast' => 'boolean',
            'isPaid' => 'boolean',
            'id' => 'int',
        ];

        $this->assertEqualsCanonicalizing($expected, $booking->getCasts());
    }
}
