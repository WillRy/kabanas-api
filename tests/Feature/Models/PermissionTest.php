<?php

namespace Tests\Feature\Models;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PermissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_if_permission_can_be_created()
    {
        \App\Models\Permission::create([
            'name' => 'edit articles',
            'description' => 'Permission to edit articles',
        ]);

        $this->assertDatabaseHas('permissions', [
            'name' => 'edit articles',
            'description' => 'Permission to edit articles',
        ]);
    }

    public function test_if_permission_has_roles_relationship()
    {
        $permission = \App\Models\Permission::create([
            'name' => 'delete articles',
            'description' => 'Permission to delete articles',
        ]);

        $this->assertTrue(method_exists($permission, 'roles'));
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsToMany::class, $permission->roles());

        $permission->roles()->attach(
            \App\Models\Role::create(['name' => 'admin'])
        );

        $this->assertCount(1, $permission->roles);
        $this->assertEquals('admin', $permission->roles->first()->name);
    }
}
