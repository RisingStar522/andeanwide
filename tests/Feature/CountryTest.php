<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Country;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class CountryTest extends TestCase
{
    use RefreshDatabase, DatabaseMigrations, WithFaker;

    /** @test */
    public function can_create_a_country()
    {
        Country::create([
            'name'              => 'Lorem',
            'abbr'              => 'LOR',
            'currency'          => 'ipsum',
            'currency_code'     => 'LPI'
        ]);

        $this->assertDatabaseHas('countries', [
            'name'              => 'Lorem',
            'abbr'              => 'LOR',
            'currency'          => 'ipsum',
            'currency_code'     => 'LPI'
        ]);
    }

    /** @test */
    public function test_case()
    {
        Country::factory()->count(5)->create();

        $this->json('get', 'api/countries')
            ->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonStructure([
                'data' => [
                    [
                        'name',
                        'abbr',
                        'currency',
                        'currency_code'
                    ]
                ]
            ]);
    }
}
