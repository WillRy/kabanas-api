<?php

namespace App\Models;

use App\Exceptions\BaseException;
use App\Http\Resources\Api\Booking\BookingResource;
use Carbon\Carbon;
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
        'numNights' => 'integer',
        'numGuests' => 'integer',
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
            throw new BaseException('Only unconfirmed bookings can be checked in.', 422);
        }

        $settings = Setting::first();

        $this->status = 'checked-in';
        $this->isPaid = true;

        if ($this->hasBreakfast) {
            $this->extrasPrice = round($settings->breakfastPrice * $this->numNights * $this->numGuests, 2);
            $this->totalPrice = round($this->propertyPrice * $this->numNights + $this->extrasPrice, 2);
        }

        $this->save();
    }

    public function checkOut()
    {
        Gate::authorize('checkOut', $this);

        if ($this->status !== 'checked-in') {
            throw new BaseException('Only checked-in bookings can be checked out.', 422);
        }

        $this->status = 'checked-out';
        $this->save();
    }

    public function stats(int $numDays)
    {
        Gate::authorize('stats', Booking::class);

        $afterDate = now()->subDays($numDays)->format('Y-m-d');

        $numBookings = 0;
        $sales = 0.0;
        $confirmedStays = 0;
        $occupancyRate = 0.0;

        $bookings = self::query()
            ->with(['guest', 'guest.user'])
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
            'confirmedStays' => BookingResource::collection($confirmedStays),
            'bookings' => BookingResource::collection($bookings),
        ];
    }

    public function todayActivities()
    {
        Gate::authorize('stats', Booking::class);

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

    public function deleteBooking()
    {
        Gate::authorize('delete', $this);

        $this->delete();
    }

    public function createBooking(array $data)
    {
        Gate::authorize('create', Booking::class);

        $booking = new Booking;

        $date1 = new \DateTime($data['startDate']);
        $date2 = new \DateTime($data['endDate']);
        $nightsDays = $date2->diff($date1)->format('%a');

        $property = Property::find($data['property_id']);

        $setting = (new Setting)->getSettings();

        if (empty($property)) {
            throw new BaseException('Property not found.', 404);
        }

        if (Carbon::parse($data['startDate'])->lt(now()->startOfDay())) {
            throw new BaseException('The start date must be today or a future date.', 422);
        }

        if (Carbon::parse($data['startDate'])->gt(Carbon::parse($data['endDate']))) {
            throw new BaseException('The start date must be greather than end date.', 422);
        }

        if ($nightsDays < $setting->minBookingLength) {
            throw new BaseException('The minimum stay is '.$setting->minBookingLength.' nights.', 422);
        }

        if ($nightsDays > $setting->maxBookingLength) {
            throw new BaseException('The maximum stay is '.$setting->maxBookingLength.' nights.', 422);
        }

        if ($data['numGuests'] > $setting->maxGuestsPerBooking) {
            throw new BaseException('The maximum number of guests for this property is '.$setting->maxGuestsPerBooking.'.', 422);
        }

        $alreadyBookedDatesInProperty = $property->getUnavailableDates($property->id);

        if (in_array($date1->format('Y-m-d'), $alreadyBookedDatesInProperty) || in_array($date2->format('Y-m-d'), $alreadyBookedDatesInProperty)) {
            throw new BaseException('The property is already booked for the selected dates.', 422);
        }

        $data = [
            'startDate' => $data['startDate'],
            'endDate' => $data['endDate'],
            'numNights' => $nightsDays,
            'numGuests' => $data['numGuests'],
            'propertyPrice' => $property->regularPrice - $property->discount,
            'extrasPrice' => 0,
            'totalPrice' => ($property->regularPrice - $property->discount) * $nightsDays,
            'status' => 'unconfirmed',
            'hasBreakfast' => false,
            'isPaid' => false,
            'observations' => $data['observations'] ?? null,
            'guest_id' => $data['guest_id'],
            'property_id' => $data['property_id'],
        ];

        $booking->fill($data);
        $booking->save();

        return $booking;
    }
}
