<?php

namespace App\Models;

use App\Policies\PropertyPolicy;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Gate;

class Property extends Model
{
    /** @use HasFactory<\Database\Factories\PropertyFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'maxCapacity',
        'regularPrice',
        'discount',
        'description',
        'image',
    ];

    protected $casts = [
        'regularPrice' => 'float',
        'discount' => 'float',
        'maxCapacity' => 'integer',
    ];

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function newProperty(array $attributes = [])
    {
        Gate::authorize('create', Property::class);

        $property = self::create($attributes);

        if (! empty($attributes['image'])) {
            $attributes['image'] = $attributes['image']->store('properties', 'public');
        }

        $property->fill($attributes);

        $property->save();

        return $property;
    }

    public function list(string $sortBy = 'id', string $sortOrder = 'asc', ?string $discountFilter = null)
    {
        Gate::authorize('viewAny', Property::class);

        $sortBy = in_array($sortBy, ['id', 'name', 'regularPrice', 'discount']) ? $sortBy : 'id';

        $sortOrder = in_array($sortOrder, ['asc', 'desc']) ? $sortOrder : 'asc';

        return Property::query()
            ->when($discountFilter, function ($query) use ($discountFilter) {
                if ($discountFilter === 'with-discount') {
                    $query->whereNotNull('discount');
                } elseif ($discountFilter === 'without-discount') {
                    $query->whereNull('discount');
                }
            })
            ->orderBy($sortBy, $sortOrder)
            ->paginate(10);
    }

    public function updateProperty(array $attributes = [])
    {
        Gate::authorize('update', $this);

        if (! empty($attributes['image'])) {
            $attributes['image'] = $attributes['image']->store('properties', 'public');
        }

        $this->fill($attributes);

        $this->save();

        return $this;
    }

    public function deleteProperty()
    {
        Gate::authorize('delete', $this);

        $this->delete();
    }

    public function getUnavailableDates(int $propertyId)
    {
        $bookings = Booking::query()
            ->where("startDate", '>=', now()->format('Y-m-d'))
            ->where('property_id', $propertyId)
            ->orderBy('startDate')
            ->get();

        $bookedDates = [];

        foreach ($bookings as $booking) {
            $period = \Carbon\CarbonPeriod::create($booking->startDate, $booking->endDate);
            foreach ($period as $date) {
                $bookedDates[] = $date->format('Y-m-d');
            }
        }

        return array_values(array_unique($bookedDates));
    }
}
