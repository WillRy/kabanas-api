<?php

namespace Tests\Feature\Models;

use App\Models\Booking;
use App\Models\Property;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PropertyTest extends TestCase
{
    use RefreshDatabase;

    public function testIfAuthorizedUserCanCreateProperty()
    {
        $this->seed();

        $this->actingAsAdmin();

        Storage::fake('public');
        $createdData = [
            'name' => 'Test Property',
            'maxCapacity' => 4,
            'regularPrice' => 100.0,
            'discount' => 10.0,
            'description' => 'A test property description',
            'image' => UploadedFile::fake()->image('property.jpg'),
        ];

        $property = (new Property())->newProperty($createdData);

        $this->assertDatabaseHas('properties', [
            'id' => $property->id,
            ...$createdData,
            'image' => $property->image,
        ]);
    }

    public function testIfAuthorizedUserCannotCreateProperty()
    {
        $this->seed();

        $user = (new User())->factory(1)->create()->first();

        $this->actingAs($user);

        Storage::fake('public');
        $createdData = [
            'name' => 'Test Property',
            'maxCapacity' => 4,
            'regularPrice' => 100.0,
            'discount' => 10.0,
            'description' => 'A test property description',
            'image' => UploadedFile::fake()->image('property.jpg'),
        ];


        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);
        (new Property())->newProperty($createdData);
    }

    public function testIfAuthorizedUserCanAccessPropertyList()
    {
        $this->seed();

        $this->actingAsAdmin();


        $properties = (new Property())->list();

        $this->assertInstanceOf(\Illuminate\Pagination\LengthAwarePaginator::class, $properties);
        $this->assertGreaterThan(0, $properties->total());
        $this->assertCount(10, $properties->items());
        $this->assertEquals(1, $properties->currentPage());
        $this->assertGreaterThan(1, $properties->lastPage());

        \Illuminate\Pagination\Paginator::currentPageResolver(function () {
            return 2;
        });

        $propertiesPage2 = (new Property())->list();

        $this->assertInstanceOf(\Illuminate\Pagination\LengthAwarePaginator::class, $propertiesPage2);
        $this->assertEquals(2, $propertiesPage2->currentPage());
        $this->assertGreaterThan(0, count($propertiesPage2->items()));
    }

    public function testIfListPropertiesFiltersIsWorking()
    {
        $this->seed();

        $this->actingAsAdmin();


        $properties = (new Property())->list('id', 'desc', null);
        $this->assertCount(10, $properties->items());
        $this->assertGreaterThanOrEqual($properties->items()[1]->id, $properties->items()[0]->id);

        $properties = (new Property())->list('id', 'asc', null);
        $this->assertCount(10, $properties->items());
        $this->assertGreaterThanOrEqual($properties->items()[0]->id, $properties->items()[1]->id);

        $properties = (new Property())->list('id', 'asc', 'with-discount');
        $discounts = array_filter($properties->items(), function ($property) {
            return $property->discount !== null;
        });
        $this->assertEquals(count($properties->items()), count($discounts));

        $properties = (new Property())->list('id', 'asc', 'without-discount');
        $withoutDiscounts = array_filter($properties->items(), function ($property) {
            return $property->discount == null;
        });
        $this->assertEquals(count($properties->items()), count($withoutDiscounts));
    }

    public function testIfUnauthorizedUserCannotAccessPropertyList()
    {
        $this->seed();

        $user = User::factory(1)->create()->first();

        $this->actingAs($user);

        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);
        (new Property())->list();
    }

    public function testIfAuthorizedUserCanUpdateProperty()
    {
        $this->seed();

        $this->actingAsAdmin();

        Storage::fake('public');
        $updateProperty = [
            'id' => 1,
            'name' => 'Test Property',
            'maxCapacity' => 4,
            'regularPrice' => 100.0,
            'discount' => 10.0,
            'description' => 'A test property description',
            'image' => UploadedFile::fake()->image('property.jpg'),
        ];

        $property = Property::find(1);
        $property->updateProperty($updateProperty);

        $this->assertDatabaseHas('properties', [
            'id' => $property->id,
            ...$updateProperty,
            'image' => $property->image,
        ]);
    }

    public function testIfUnauthorizedUserCannotUpdateProperty()
    {
        $this->seed();

        $user = User::factory(1)->create()->first();

        $this->actingAs($user);

        Storage::fake('public');
        $updateProperty = [
            'id' => 1,
            'name' => 'Test Property',
            'maxCapacity' => 4,
            'regularPrice' => 100.0,
            'discount' => 10.0,
            'description' => 'A test property description',
            'image' => UploadedFile::fake()->image('property.jpg'),
        ];

        $property = Property::find(1);

        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);
        $property->updateProperty($updateProperty);
    }

    public function testIfAuthorizedUserCanDeleteProperty()
    {
        $this->seed();

        $this->actingAsAdmin();

        $property = Property::find(1);
        $property->deleteProperty();

        $this->assertDatabaseMissing('properties', [
            'id' => $property->id,
            'deleted_at' => null,
        ]);
    }

    public function testIfUnauthorizedUserCannotDeleteProperty()
    {
        $this->seed();

        $user = User::factory(1)->create()->first();

        $this->actingAs($user);

        $property = Property::find(1);

        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);
        $property->deleteProperty();
    }

    public function testIfCanGetUnavailableDates()
    {
        $this->seed();

        $this->actingAsAdmin();

        $property = Property::factory()->create();

        $period = \Carbon\CarbonPeriod::create(now(), now()->addDays(5));
        $periodDays = array_map(function (Carbon $date) {
            return $date->format('Y-m-d');
        }, $period->toArray());

        Booking::factory()->create([
            'property_id' => $property->id,
            'startDate' => $periodDays[0],
            'endDate' => $periodDays[count($periodDays) - 1],
        ]);

        $unavailableDates = $property->getUnavailableDates($property->id);

        $this->assertIsArray($unavailableDates);

        $this->assertEqualsCanonicalizing(
            $periodDays,
            $unavailableDates
        );
    }
}
