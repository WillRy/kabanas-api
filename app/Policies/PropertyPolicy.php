<?php

namespace App\Policies;

use App\Models\Property;
use App\Models\User;

class PropertyPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        $permissions = $user->userPermissions();

        return in_array('manage-properties', $permissions, true);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        $permissions = $user->userPermissions();

        return in_array('manage-properties', $permissions, true);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Property $property): bool
    {
        $permissions = $user->userPermissions();

        return in_array('manage-properties', $permissions, true);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Property $property): bool
    {
        $permissions = $user->userPermissions();

        return in_array('manage-properties', $permissions, true);
    }


}
