<?php

namespace Tests\Feature\Controllers\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileControllerTest extends TestCase
{
    use RefreshDatabase;

    public function testIfProfileUpdateValidationWorks(): void
    {
        $this->seed();

        $user = \App\Models\User::factory()->create();

        $this->actingAs($user);

        $response = $this->postJson('/api/profile', [
            'name' => str_repeat('a', 400),
            'email' => 'not-an-email',
            'password' => 'short',
            'avatar' => 'not-a-file',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name', 'email', 'password', 'avatar']);
    }

    public function testIfProfileIsUpdatedSuccessfully(): void
    {
        $this->seed();

        $user = \App\Models\User::factory()->create();

        $this->actingAs($user);

        Storage::fake('public');
        $response = $this->postJson('/api/profile', [
            'name' => 'Updated Name',
            'email' => "admin2@admin.com",
            'password' => 'newsecurepassword',
            "password_confirmation" => 'newsecurepassword',
            'avatar' => UploadedFile::fake()->image('avatar.jpg'),
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'id',
                'name',
                'email',
                'avatar',
                'permissions'
            ],
        ]);

        $user = User::find($user->id);

        $this->assertTrue(Hash::check('newsecurepassword', $user->password));

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
            'email' => "admin2@admin.com"
        ]);

    }
}
