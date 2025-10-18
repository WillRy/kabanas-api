<?php

namespace App\Policies;

use App\Models\Booking;
use App\Models\User;

class BookingPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        $permissions = $user->userPermissions();

        return in_array('manage-bookings', $permissions, true);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Booking $booking): bool
    {
        $permissions = $user->userPermissions();
        $canSeeAll = in_array('manage-bookings', $permissions, true);

        /** @var \App\Models\Guest $guestProfile */
        $guestProfile = $user->guestProfile;

         /** @var \App\Models\Guest $bookingGuest */
        $bookingGuest = $booking->guest;

        $isOwner = $guestProfile && $guestProfile->id === $bookingGuest->id;

        return $canSeeAll || $isOwner;
    }


    public function checkIn(User $user, Booking $booking): bool
    {
        $permissions = $user->userPermissions();

        return in_array('manage-bookings', $permissions, true);
    }

    public function stats(User $user): bool
    {
        $permissions = $user->userPermissions();

        return in_array('manage-bookings', $permissions, true);
    }

    public function checkOut(User $user): bool
    {
        $permissions = $user->userPermissions();

        return in_array('manage-bookings', $permissions, true);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user): bool
    {
        $permissions = $user->userPermissions();

        return in_array('manage-bookings', $permissions, true);
    }

}
