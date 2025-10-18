<?php

namespace Tests\Feature\Models;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class PermissionTest extends TestCase
{

    use RefreshDatabase;

    public function testIfPermissionCanBeCreated()
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

    public function testIfPermissionHasRolesRelationship()
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
