<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class GuestControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_if_guest_autocomplete_works()
    {
        $this->seed();

        $this->actingAsAdmin();

        $guests = \App\Models\Guest::factory(2)->create();

        $names = $guests->map(function ($guest) {
            return explode(' ', $guest->user->name)[0];
        });

        foreach ($names as $name) {
            $response = $this->getJson("/api/guest/autocomplete?search={$name}");

            $response->assertStatus(200);

            $response->assertJson(function (AssertableJson $json) {
                $json
                    ->has('data')
                    ->whereAllType([
                        'data.0.id' => 'integer',
                        'data.0.name' => 'string',
                    ])
                    ->etc();
            });

            $data = $response->json('data');
            foreach ($data as $guest) {
                $this->assertStringContainsStringIgnoringCase($name, $guest['name']);
            }
        }
    }
}
