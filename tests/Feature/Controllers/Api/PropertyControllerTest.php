<?php

namespace Tests\Feature\Controllers\Api;

use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PropertyControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    public function testIfPropertyCreationFailsIfUnauthenticated(): void
    {
        $response = $this->postJson('/api/property');

        $response->assertStatus(401);
    }

    public function testIfPropertyCreationFailsIfNotAdmin(): void
    {
        $this->seed();

        $user = User::factory(1)->create()->first();

        $this->actingAs($user);

        $response = $this->postJson('/api/property', [
            'name' => 'Test Property',
            'maxCapacity' => 10,
            'regularPrice' => 100.00,
            'discount' => 10,
            'description' => "Random description",
            'image' => null,
        ]);

        $response->assertStatus(403);
    }



    public function testIfPropertiesCanBeListed(): void
    {
        $this->seed();

        $this->actingAsAdmin();

        $response = $this->getJson('/api/property');

        $response->assertStatus(200);

        $response->assertJsonStructure([
            'data' => [
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'maxCapacity',
                        'regularPrice',
                        'discount',
                        'description',
                        'image',
                        'created_at',
                        'updated_at',
                    ],
                ],
            ],
        ]);

        $response = $this->getJson('/api/property?discount=with-discount');

        $response->assertStatus(200);
        $responseData = $response->json('data.data');
        $discountNull = array_filter($responseData, function ($property) {
            return $property['discount'] === null;
        });
        $this->assertEmpty($discountNull);

        $response = $this->getJson('/api/property?discount=without-discount');
        $response->assertStatus(200);
        $responseData = $response->json('data.data');
        $withDiscounts = array_filter($responseData, function ($property) {
            return $property['discount'] !== null;
        });
        $this->assertEmpty($withDiscounts);

        $response = $this->getJson('/api/property?sortBy=id&sortOrder=desc');
        $response->assertStatus(200);
        $responseData = $response->json('data.data');
        $this->assertFalse($responseData[0]['id'] < $responseData[1]['id']);

        $response = $this->getJson('/api/property?sortBy=name&sortOrder=desc');
        $response->assertStatus(200);
        $responseData = $response->json('data.data');
        $this->assertFalse($responseData[0]['name'] < $responseData[1]['name']);

        $response = $this->getJson('/api/property?sortBy=regularPrice&sortOrder=desc');
        $response->assertStatus(200);
        $responseData = $response->json('data.data');
        $this->assertFalse($responseData[0]['regularPrice'] < $responseData[1]['regularPrice']);

        $response = $this->getJson('/api/property?sortBy=discount&sortOrder=desc');
        $response->assertStatus(200);
        $responseData = $response->json('data.data');
        $this->assertFalse($responseData[0]['discount'] < $responseData[1]['discount']);
    }

    public function testIfValidatonWorks(): void
    {
        $this->seed();

        $this->actingAsAdmin();

        $response = $this->postJson('/api/property', [
            'name' => $this->faker->text(400),
            'maxCapacity' => $this->faker->text(300),
            'regularPrice' => $this->faker->text(300),
            'discount' => $this->faker->text(300),
            'description' => 1000,
            'image' => "xpto",
        ]);


        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'name',
            'maxCapacity',
            'regularPrice',
            'discount',
            'description',
            'image',
        ]);


        Storage::fake('public');
        $response = $this->postJson('/api/property', [
            'name' => $this->faker->text(30),
            'maxCapacity' => $this->faker->numberBetween(1, 10),
            'regularPrice' => mt_rand(100, 1000),
            'discount' => mt_rand(1100, 1200),
            'description' => "Random description",
            'image' => UploadedFile::fake()->image('property.jpg'),
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'discount',
        ]);
    }

    public function testIfPropertyIsCreatedSuccessfullyWith(): void
    {
        $this->seed();

        $this->actingAsAdmin();

        Storage::fake('public');
        $response = $this->postJson('/api/property', [
            'name' => $this->faker->text(30),
            'maxCapacity' => $this->faker->numberBetween(1, 10),
            'regularPrice' => mt_rand(100, 1000),
            'discount' => mt_rand(1, 50),
            'description' => "Random description",
            'image' => UploadedFile::fake()->image('property.jpg'),
        ]);

        $response->assertStatus(201);

        $response->assertJsonStructure([
            'message',
            'data' => [
                'id',
                'name',
                'maxCapacity',
                'regularPrice',
                'discount',
                'description',
                'image',
                'created_at',
                'updated_at',
            ],
        ]);

        $this->assertDatabaseHas('properties', [
            'id' => $response->json('data.id'),
            'name' => $response->json('data.name'),
            'maxCapacity' => $response->json('data.maxCapacity'),
            'regularPrice' => $response->json('data.regularPrice'),
            'discount' => $response->json('data.discount'),
            'description' => $response->json('data.description'),
        ]);
    }

    public function testIfPropertyCanBeDeleted(): void
    {
        $this->seed();

        $this->actingAsAdmin();

        $property = Property::find(1);

        $this->delete("/api/property/{$property->id}");

        $this->assertDatabaseMissing('properties', [
            'id' => $property->id,
            'deleted_at' => null,
        ]);
    }

    public function testIfUpdateFailsIfNotAdmin(): void
    {
        $this->seed();

        $user = User::factory(1)->create()->first();

        $this->actingAs($user);

        $property = Property::find(1);

        $response = $this->postJson("/api/property/{$property->id}", [
            'name' => 'Updated Property',
            'maxCapacity' => 20,
            'regularPrice' => 200.00,
            'discount' => 20,
            'description' => "Updated description",
            'image' => null,
        ]);

        $response->assertStatus(403);
    }

    public function testIfUpdateValidationWorks(): void
    {
        $this->seed();

        $this->actingAsAdmin();

        $property = Property::find(1);

        $response = $this->postJson("/api/property/{$property->id}", [
            'name' => $this->faker->text(500),
            'maxCapacity' => $this->faker->text(500),
            'regularPrice' => $this->faker->text(300),
            'discount' => $this->faker->text(300),
            'description' => 1000,
            'image' => "xpto",
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'name',
            'maxCapacity',
            'regularPrice',
            'discount',
            'description',
            'image',
        ]);

        Storage::fake('public');
        $response = $this->postJson("/api/property/{$property->id}", [
            'name' => $this->faker->text(30),
            'maxCapacity' => $this->faker->numberBetween(1, 10),
            'regularPrice' => mt_rand(100, 1000),
            'discount' => mt_rand(1100, 1200),
            'description' => "Random description",
            'image' => UploadedFile::fake()->image('property.jpg'),
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'discount',
        ]);
    }

    public function testIfUpdateWorks(): void
    {
        $this->seed();

        $this->actingAsAdmin();

        $property = Property::find(1);

        $response = $this->postJson("/api/property/{$property->id}", [
            'name' => 'Updated Property',
            'maxCapacity' => 20,
            'regularPrice' => 200.00,
            'discount' => 20,
            'description' => "Updated description",
            'image' => null,
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'id',
                'name',
                'maxCapacity',
                'regularPrice',
                'discount',
                'description',
                'image',
                'created_at',
                'updated_at',
            ]
        ]);

        $this->assertDatabaseHas('properties', [
            'id' => $property->id,
            'name' => 'Updated Property',
            'maxCapacity' => 20,
            'regularPrice' => 200.00,
            'discount' => 20,
            'description' => "Updated description",
        ]);
    }
}
