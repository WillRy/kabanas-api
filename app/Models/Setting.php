<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

class Setting extends Model
{
    /** @use HasFactory<\Database\Factories\SettingFactory> */
    use HasFactory;

    protected $fillable = [
        'minBookingLength',
        'maxBookingLength',
        'maxGuestsPerBooking',
        'breakfastPrice',
    ];

    public array $defaultSettings = [
        'minBookingLength' => 1,
        'maxBookingLength' => 30,
        'maxGuestsPerBooking' => 5,
        'breakfastPrice' => 10.00,
    ];

    protected function casts(): array
    {
        return [
            'minBookingLength' => 'integer',
            'maxBookingLength' => 'integer',
            'maxGuestsPerBooking' => 'integer',
            'breakfastPrice' => 'decimal:2',
        ];
    }

    public function getSettings(): Setting
    {

        $settings = self::first();

        if (! $settings) {
            $settings = $this->initializeSettings();
        }

        return $settings;
    }

    public function initializeSettings(): Setting
    {
        $alreadyExists = self::first();
        if ($alreadyExists) {
            return $alreadyExists;
        }

        $settings = new self($this->defaultSettings);

        $settings->save();

        return $settings;
    }

    public function updateSettings(array $data): Setting
    {
        Gate::authorize('update', Setting::class);

        $settings = self::first();

        if (! $settings) {
            $settings = $this->initializeSettings();
        }

        $settings->fill($data);

        $settings->save();

        return $settings;
    }
}
