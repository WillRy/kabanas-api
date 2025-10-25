<?php

namespace App\Policies;

use App\Models\User;

class SettingPolicy
{
    public function update(User $user): bool
    {
        $permissions = $user->userPermissions();

        return in_array('settings', $permissions, true);
    }
}
