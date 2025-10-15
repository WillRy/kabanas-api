<?php

namespace App\Http\Resources\Api\Booking;

use App\Http\Resources\Api\Property\PropertyResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'startDate' => $this->startDate->toDateString(),
            'endDate' => $this->endDate->toDateString(),
            'numNights' => $this->numNights,
            'numGuests' => $this->numGuests,
            'propertyPrice' => $this->propertyPrice,
            'extrasPrice' => $this->extrasPrice,
            'totalPrice' => $this->totalPrice,
            'status' => $this->status,
            'hasBreakfast' => $this->hasBreakfast,
            'isPaid' => $this->isPaid,
            'observations' => $this->observations,
            'guest' => [
                'id' => $this->guest->id,
                'name' => $this->guest->user->name,
                'email' => $this->guest->user->email,
                'countryFlag' => mb_strtolower($this->guest->countryFlag),
            ],
            'property' => new PropertyResource($this->property),
            'created_at' => $this->created_at->toDateTimeString(),
            'updated_at' => $this->updated_at->toDateTimeString(),
        ];
    }
}
