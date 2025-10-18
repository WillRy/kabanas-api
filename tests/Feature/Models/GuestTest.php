<?php

namespace Tests\Feature\Models;

use App\Models\Booking;
use App\Models\Guest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class GuestTest extends TestCase
{
    use RefreshDatabase;

    public function testIfGuestCanBeCreated()
    {
        $this->seed();

        \App\Models\Guest::create([
            'nationalID' => '123456789',
            'nationality' => 'American',
            'countryFlag' => '🇺🇸',
            'user_id' => \App\Models\User::first()->id,
        ]);

        $this->assertDatabaseHas('guests', [
            'nationalID' => '123456789',
            'nationality' => 'American',
            'countryFlag' => '🇺🇸',
            'user_id' => \App\Models\User::first()->id,
        ]);
    }

    public function testIfGuestHaveUserRelation()
    {
        $this->seed();

        $user = \App\Models\User::first();

        $guest = \App\Models\Guest::create([
            'nationalID' => '123456789',
            'nationality' => 'American',
            'countryFlag' => '🇺🇸',
            'user_id' => $user->id,
        ]);

        $this->assertTrue(method_exists($guest, 'user'));
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $guest->user());
        $this->assertEquals($user->id, $guest->user->id);
    }

    public function testIfGuestHaveBookingsRelation()
    {
        $this->seed();

        $booking = Booking::inRandomOrder()->first();

        $guest = Guest::query()->whereHas('bookings', function ($query) use ($booking) {
            $query->where('id', $booking->id);
        })->first();

        $this->assertTrue(method_exists($guest, 'bookings'));
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $guest->bookings());
        $this->assertTrue($guest->bookings->contains($booking));
    }
}
