<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Bank;
use App\Models\Country;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class BankTest extends TestCase
{
    use DatabaseMigrations, RefreshDatabase;

    /** @test */
    public function can_be_created_a_bank()
    {
        $bank = Bank::create([
            'country_id'    => 1,
            'name'          => 'bank name',
            'abbr'          => 'abc',
            'code'          => '123',
            'is_active'     => true
        ]);

        $this->assertInstanceOf(Bank::class, $bank);
        $this->assertDatabaseHas('banks', [
            'country_id'    => 1,
            'name'          => 'bank name',
            'abbr'          => 'abc',
            'code'          => '123',
            'is_active'     => true
        ]);
    }

    /** @test */
    public function banks_belongs_to_a_country()
    {
        $country = Country::factory()->create();
        $bank = Bank::factory()->create([
            'country_id' => $country->id
        ]);

        $this->assertInstanceOf(Country::class, $bank->country);
        $this->assertInstanceOf(Collection::class, $country->banks);
        $this->assertInstanceOf(Bank::class, $country->banks[0]);
    }
}
