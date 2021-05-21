<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Address;
use App\Models\Country;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class AddressTest extends TestCase
{
    use DatabaseMigrations, RefreshDatabase, WithFaker;

    /** @test */
    public function can_create_a_address_record()
    {
        Address::create([
            'user_id'           => 1,
            'country_id'        => 1,
            'address'           => 'Fake Av. 123',
            'address_ext'       => 'Fake address',
            'state'             => 'New State',
            'city'              => 'New City',
            'cod'               => '123456',
        ]);

        $this->assertDatabaseHas('addresses', [
            'user_id'           => 1,
            'country_id'        => 1,
            'address'           => 'Fake Av. 123',
            'address_ext'       => 'Fake address',
            'state'             => 'New State',
            'city'              => 'New City',
            'cod'               => '123456',
        ]);
    }

    /** @test */
    public function an_address_belongs_to_user()
    {
        $user = User::factory()->create();
        $address = Address::factory()->create([
            'user_id' => $user->id
        ]);

        $this->assertInstanceOf(User::class, $address->user);
        $this->assertInstanceOf(Address::class, $user->address);
    }

    /** @test */
    public function an_adddres_has_one_country()
    {
        $country = Country::factory()->create();
        $user = User::factory()->create();
        $address = Address::factory()->create([
            'user_id'       => $user->id,
            'country_id'    => $country->id
        ]);

        $this->assertInstanceOf(Country::class, $address->country);
    }

    /** @test */
    public function address_is_verified()
    {
        $address = Address::factory()->create();
        $this->assertFalse($address->isVerified);

        $address->verified_at = now();
        $address->save();
        $this->assertTrue($address->isVerified);
    }

    /** @test */
    public function address_is_rejected()
    {
        $address = Address::factory()->create();
        $this->assertFalse($address->isRejected);

        $address->rejected_at = now();
        $address->save();
        $this->assertTrue($address->isRejected);
    }
}
