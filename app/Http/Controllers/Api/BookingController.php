<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\Booking\BookingResource;
use App\Models\Booking;
use App\Service\ResponseJSON;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class BookingController extends Controller
{
    public function index(Request $request)
    {
        $bookings = (new Booking)->list(
            $request->query('sortBy', 'id'),
            $request->query('sortOrder', 'asc'),
            $request->query('status'),
        );

        $data = ResponseJSON::fromPaginate($bookings, BookingResource::collection($bookings));

        return ResponseJSON::getInstance()
            ->setData($data)
            ->render();
    }

    public function view(int $bookingId)
    {
        $booking = (new Booking)->details($bookingId);

        if(!$booking) {
            return ResponseJSON::getInstance()
                ->setMessage('Booking not found')
                ->setStatusCode(404)
                ->render();
        }

        Gate::authorize('view', $booking);

        return ResponseJSON::getInstance()
            ->setData(new BookingResource($booking))
            ->render();
    }

    public function checkIn(Request $request, Booking $booking)
    {
        $booking->fill([
            'hasBreakfast' => $request->input('hasBreakfast', $booking->hasBreakfast),
        ]);

        $booking->checkIn();

        return ResponseJSON::getInstance()
            ->setMessage('Booking checked in successfully')
            ->setData(new BookingResource($booking))
            ->setStatusCode(200)
            ->render();
    }

    public function checkOut(Booking $booking)
    {
        $booking->checkOut();

        return ResponseJSON::getInstance()
            ->setMessage('Booking checked out successfully')
            ->setData(new BookingResource($booking))
            ->setStatusCode(200)
            ->render();
    }

    public function stats(Request $request)
    {
        $numDays = (int) $request->query('last', 7);

        $stats = (new Booking)->stats($numDays);

        return ResponseJSON::getInstance()
            ->setData($stats)
            ->render();
    }

    public function destroy(Booking $booking)
    {
        $booking->deleteBooking();

        return response()->noContent();
    }

    public function todayActivity()
    {
        $bookings = (new Booking)->todayActivities();

        return ResponseJSON::getInstance()
            ->setData(BookingResource::collection($bookings))
            ->render();
    }
}
