<?php

namespace App\Http\Resources\Api\Setting;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SettingResource extends JsonResource
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
            'minBookingLength' => $this->minBookingLength,
            'maxBookingLength' => $this->maxBookingLength,
            'maxGuestsPerBooking' => $this->maxGuestsPerBooking,
            'breakfastPrice' => $this->breakfastPrice,
        ];
    }
}
