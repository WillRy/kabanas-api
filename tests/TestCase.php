<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Artisan;

abstract class TestCase extends BaseTestCase
{
    public function getAdmin()
    {
        $user = User::query()->whereHas('roles', function ($q) {
            $q->where('name', 'manager');
        })->first();

        return $user;
    }

    public function actingAsAdmin(?string $guard = null)
    {
        $user = $this->getAdmin();

        return $this->actingAs($user, $guard);
    }

    public function getNotAdminUser()
    {
        $user = User::query()->whereDoesntHave('roles', function ($q) {
            $q->where('name', 'manager');
        })->first();

        return $user;
    }

    public function actingAsNotAdminUser(?string $guard = null)
    {
        $user = $this->getNotAdminUser();

        return $this->actingAs($user, $guard);
    }
}
