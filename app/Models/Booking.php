<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Gate;

class Booking extends Model
{
    /** @use HasFactory<\Database\Factories\BookingFactory> */
    use HasFactory;

    protected $fillable = [
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

    protected $casts = [
        'startDate' => 'datetime',
        'endDate' => 'datetime',
        'propertyPrice' => 'float',
        'extrasPrice' => 'float',
        'totalPrice' => 'float',
        'hasBreakfast' => 'boolean',
        'isPaid' => 'boolean',
    ];

    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class);
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class)->withTrashed();
    }

    public function list(string $sortBy = 'id', string $sortOrder = 'asc', ?string $statusFilter = null)
    {
        Gate::authorize('viewAny', Booking::class);

        $sortBy = in_array($sortBy, ['id', 'startDate', 'totalPrice']) ? $sortBy : 'id';

        $sortOrder = in_array($sortOrder, ['asc', 'desc']) ? $sortOrder : 'asc';

        return self::query()
            ->when($statusFilter, function ($query) use ($statusFilter) {
                if ($statusFilter === 'checked-out') {
                    $query->whereStatus('checked-out');
                } elseif ($statusFilter === 'checked-in') {
                    $query->whereStatus('checked-in');
                } elseif ($statusFilter === 'unconfirmed') {
                    $query->whereStatus('unconfirmed');
                }
            })
            ->with(['guest', 'guest.user', 'property'])
            ->orderBy($sortBy, $sortOrder)
            ->paginate(10);
    }

    public function details(int $bookingId)
    {
        return self::query()
            ->with(['guest', 'guest.user', 'property'])
            ->where('id', $bookingId)
            ->first();
    }

    public function checkIn()
    {
        Gate::authorize('checkIn', $this);

        if ($this->status !== 'unconfirmed') {
            throw new \Exception('Only unconfirmed bookings can be checked in.');
        }

        $settings = Setting::first();

        $this->status = 'checked-in';
        $this->isPaid = true;

        if ($this->hasBreakfast) {
            $this->totalPrice += $settings->breakfastPrice * $this->numNights * $this->numGuests;
            $this->extrasPrice = $settings->breakfastPrice * $this->numNights * $this->numGuests;
        }

        $this->save();
    }

    public function checkOut()
    {
        Gate::authorize('checkOut', $this);

        if ($this->status !== 'checked-in') {
            throw new \Exception('Only checked-in bookings can be checked out.');
        }

        $this->status = 'checked-out';
        $this->save();
    }

    public function bookingsAfterDate($date)
    {
        return self::query()
            ->whereRaw('DATE(created_at) >= ?', [$date])
            ->whereRaw('DATE(created_at) <= ?', [now()->format('Y-m-d')])
            ->get();
    }

    public function stats(int $numDays)
    {

        $afterDate = now()->subDays($numDays)->format('Y-m-d');

        $numBookings = 0;
        $sales = 0.0;
        $confirmedStays = 0;
        $occupancyRate = 0.0;

        $bookings = self::query()
            ->select('id', 'startDate', 'endDate', 'numNights', 'totalPrice', 'extrasPrice', 'status', 'created_at')
            ->whereRaw('DATE(created_at) >= ?', [$afterDate])
            ->whereRaw('DATE(created_at) <= ?', [now()->format('Y-m-d')])
            ->get();

        $numBookings = $bookings->count();
        $sales = round($bookings->sum('totalPrice'), 2);
        $confirmedStays = $bookings->whereIn('status', ['checked-in', 'checked-out'])->values();

        $occupancyRate = array_reduce($confirmedStays->toArray(), function ($acc, $cur) {
            return $acc + $cur['numNights'];
        }, 0) / ($numDays * Property::count());

        $occupancyRate = round($occupancyRate * 100, 2);

        return [
            'numBookings' => $numBookings,
            'sales' => $sales,
            'occupancyRate' => $occupancyRate,
            'confirmedStaysCount' => $confirmedStays->count(),
            'confirmedStays' => $confirmedStays,
            'bookings' => $bookings,
        ];
    }

    public function todayActivities()
    {
        return self::query()
            ->with(['guest', 'guest.user', 'property'])
            ->where(function ($query) {
                $query->where(function ($q) {
                    $q->whereDate('startDate', now()->format('Y-m-d'))
                        ->where('status', 'unconfirmed');
                })->orWhere(function ($q) {
                    $q->whereDate('endDate', now()->format('Y-m-d'))
                        ->where('status', 'checked-in');
                });
            })
            ->orderBy('created_at', 'asc')
            ->get();
    }
}
