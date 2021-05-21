<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Order;
use App\Models\Country;
use App\Models\Remitter;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class RemitterTest extends TestCase
{
    use DatabaseMigrations, RefreshDatabase;

    /** @test */
    public function can_create_a_remitter()
    {
        Remitter::create([
            'user_id' => 1,
            'fullname' => 'Jhon Doe',
            'document_type' => 'PASS',
            'dni' => '1234567890',
            'issuance_date' => '2020-01-01',
            'expiration_date' => '2022-01-01',
            'dob' => '2000-01-01',
            'address' => 'fake avenue #0001',
            'city' => 'city',
            'state' => 'state',
            'country_id' => 1,
            'issuance_country_id' => 1,
            'phone' => '6218041089231',
            'email' => 'jhon@test.com',
            'document_url' => '/lorem/ipsum/1',
            'reverse_url' => '/lorem/ipsum/2',
        ]);

        $this->assertDatabaseHas('remitters', [
            'user_id' => 1,
            'fullname' => 'Jhon Doe',
            'document_type' => 'PASS',
            'dni' => '1234567890',
            'issuance_date' => '2020-01-01',
            'expiration_date' => '2022-01-01',
            'dob' => '2000-01-01',
            'address' => 'fake avenue #0001',
            'city' => 'city',
            'state' => 'state',
            'country_id' => 1,
            'issuance_country_id' => 1,
            'phone' => '6218041089231',
            'email' => 'jhon@test.com',
            'document_url' => '/lorem/ipsum/1',
            'reverse_url' => '/lorem/ipsum/2',
        ]);
    }

    /** @test */
    public function an_remitter_belongs_to_an_user()
    {
        $user = User::factory()->create();
        $remitter = Remitter::factory()->create([
            'user_id' => $user->id
        ]);

        $this->assertInstanceOf(User::class, $remitter->user);
        $this->assertInstanceOf(Collection::class, $user->remitters);
        $this->assertInstanceOf(Remitter::class, $user->remitters[0]);
    }

    /** @test */
    public function an_remitter_has_many_orders()
    {
        $remitter = Remitter::factory()->create();
        $order = Order::factory()->create([
            'remitter_id' => $remitter->id
        ]);

        $this->assertInstanceOf(Remitter::class, $order->remitter);
        $this->assertInstanceOf(Collection::class, $remitter->orders);
        $this->assertInstanceOf(Order::class, $remitter->orders[0]);
    }

    /** @test */
    public function a_remitter_belongs_to_a_country()
    {
        $country = Country::factory()->create();
        $remitter = Remitter::factory()->create([
            'country_id' => $country->id
        ]);

        $this->assertInstanceOf(Country::class, $remitter->country);
    }

    /** @test */
    public function a_remitter_issuance_country_belongs_to_a_country()
    {
        $country = Country::factory()->create();
        $remitter = Remitter::factory()->create([
            'issuance_country_id' => $country->id
        ]);

        $this->assertInstanceOf(Country::class, $remitter->issuance_country);
        $this->assertEquals($country->id, $remitter->issuance_country->id);
    }
}
