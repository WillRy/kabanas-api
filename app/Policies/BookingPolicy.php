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

        $isOwner = $guestProfile->id === $bookingGuest->id;

        return $canSeeAll || $isOwner;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        $permissions = $user->userPermissions();

        return in_array('manage-bookings', $permissions, true);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Booking $booking): bool
    {
        $permissions = $user->userPermissions();

        return in_array('manage-bookings', $permissions, true);
    }

    public function checkIn(User $user, Booking $booking): bool
    {
        $permissions = $user->userPermissions();

        return in_array('manage-bookings', $permissions, true);
    }

    public function stats(User $user, Booking $booking): bool
    {
        $permissions = $user->userPermissions();

        return in_array('manage-bookings', $permissions, true);
    }

    public function checkOut(User $user, Booking $booking): bool
    {
        $permissions = $user->userPermissions();

        return in_array('manage-bookings', $permissions, true);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Booking $booking): bool
    {
        $permissions = $user->userPermissions();

        return in_array('manage-bookings', $permissions, true);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Booking $booking): bool
    {
        $permissions = $user->userPermissions();

        return in_array('manage-bookings', $permissions, true);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Booking $booking): bool
    {
        $permissions = $user->userPermissions();

        return in_array('manage-bookings', $permissions, true);
    }
}
