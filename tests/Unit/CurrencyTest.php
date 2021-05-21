<?php

namespace Tests\Unit;

use App\Models\Country;
use App\Models\Currency;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class CurrencyTest extends TestCase
{
    use DatabaseMigrations, RefreshDatabase, WithFaker;

    /** @test */
    public function can_create_a_currency()
    {
        $currency = Currency::create([
            'name'          => 'currency',
            'symbol'        => 'SYM',
            'is_active'     => true,
            'can_send'      => true,
            'can_receive'   => true,
            'country_id'    => 1,
        ]);

        $this->assertInstanceOf(Currency::class, $currency);
        $this->assertDatabaseHas('currencies', [
            'name'          => 'currency',
            'symbol'        => 'SYM',
            'is_active'     => true,
            'can_send'      => true,
            'can_receive'   => true,
            'country_id'    => 1,
        ]);
    }

    /** @test */
    public function a_currency_belongs_to_a_country()
    {
        $country = Country::factory()->create();
        $currency = Currency::factory()->create([
            'country_id'    => $country->id,
        ]);

        $this->assertInstanceOf(Country::class, $currency->country);
    }
}
