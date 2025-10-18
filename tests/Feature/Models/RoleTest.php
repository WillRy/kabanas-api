<?php

namespace Tests\Feature\Models;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class RoleTest extends TestCase
{
    use RefreshDatabase;

    public function testRoleCanBeCreated(): void
    {
        $roleData = [
            'name' => 'admin',
            'description' => 'Administrator role',
        ];

        \App\Models\Role::create($roleData);

        $this->assertDatabaseHas('roles', $roleData);
    }

    public function testRoleCanHavePermissions(): void
    {
        $roleData = [
            'name' => 'admin',
            'description' => 'Administrator role',
        ];

        $role = \App\Models\Role::create($roleData);

        $role->permissions()->createMany([
            ['name' => 'edit_posts', 'description' => 'Edit posts'],
            ['name' => 'delete_posts', 'description' => 'Delete posts'],
        ]);

        $this->assertDatabaseHas('permissions', ['name' => 'edit_posts']);
        $this->assertDatabaseHas('permissions', ['name' => 'delete_posts']);

        $permissionsInRole = $role->permissions;

        $this->assertEquals(2, $permissionsInRole->count());
        $this->assertEquals('edit_posts', $permissionsInRole[0]->name);
        $this->assertEquals('delete_posts', $permissionsInRole[1]->name);
    }
}
