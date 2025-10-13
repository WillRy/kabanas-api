<?php

namespace App\Policies;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class BookingPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        $permissions = $user->roles->flatMap->permissions->pluck('name')->toArray();
        return in_array('manage-bookings', $permissions, true);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Booking $booking): bool
    {
        $permissions = $user->roles->flatMap->permissions->pluck('name')->toArray();
        $canSeeAll = in_array('manage-bookings', $permissions, true);
        $isOwner = $user->guest && $user->guest->id === $booking->guest->guest_id;

        return $canSeeAll || $isOwner;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        $permissions = $user->roles->flatMap->permissions->pluck('name')->toArray();
        return in_array('manage-bookings', $permissions, true);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Booking $booking): bool
    {
        $permissions = $user->roles->flatMap->permissions->pluck('name')->toArray();
        return in_array('manage-bookings', $permissions, true);
    }

    public function checkIn(User $user, Booking $booking): bool
    {
        $permissions = $user->roles->flatMap->permissions->pluck('name')->toArray();
        return in_array('manage-bookings', $permissions, true);
    }

    public function stats(User $user, Booking $booking): bool
    {
        $permissions = $user->roles->flatMap->permissions->pluck('name')->toArray();
        return in_array('manage-bookings', $permissions, true);
    }

    public function checkOut(User $user, Booking $booking): bool
    {
        $permissions = $user->roles->flatMap->permissions->pluck('name')->toArray();
        return in_array('manage-bookings', $permissions, true);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Booking $booking): bool
    {
        $permissions = $user->roles->flatMap->permissions->pluck('name')->toArray();
        return in_array('manage-bookings', $permissions, true);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Booking $booking): bool
    {
        $permissions = $user->roles->flatMap->permissions->pluck('name')->toArray();
        return in_array('manage-bookings', $permissions, true);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Booking $booking): bool
    {
        $permissions = $user->roles->flatMap->permissions->pluck('name')->toArray();
        return in_array('manage-bookings', $permissions, true);
    }
}
