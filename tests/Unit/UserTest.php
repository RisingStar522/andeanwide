<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Address;
use App\Models\Company;
use App\Models\Currency;
use App\Models\Identity;
use App\Models\Recipient;
use App\Models\Remitter;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class UserTest extends TestCase
{
    use DatabaseMigrations, RefreshDatabase;

    /** @test */
    public function can_create_a_user()
    {
        $user = User::create([
            'name' => 'username',
            'email' => 'test@email.com',
            'password' => 'abcd12345',
            'balance' => 1000,
            'balance_credit_limit' => 200,
            'account_type' => 'personal',
            'balance_currency_id' => 1
        ]);

        $this->assertNotNull($user);
        $this->assertDatabaseHas('users', [
            'name' => 'username',
            'email' => 'test@email.com',
            'balance' => 1000,
            'balance_credit_limit' => 200,
            'account_type' => 'personal',
            'balance_currency_id' => 1
        ]);
    }

    /** @test */
    public function can_compute_available_amount()
    {
        $user = User::factory()->create([
            'balance' => 1000,
            'balance_credit_limit' => 0,
        ]);

        $this->assertEquals(1000, $user->availableAmount);
        $user->balance_credit_limit = 500;
        $this->assertEquals(1500, $user->availableAmount);
    }

    /** @test */
    public function user_has_one_balance_currency()
    {
        $currency = Currency::factory()->create();
        $user = User::factory()->create([
            'balance_currency_id' => $currency->id,
        ]);
        $this->assertInstanceOf(Currency::class, $user->currency);
    }

    /** @test */
    public function user_has_one_identity()
    {
        $user = User::factory()->create();
        $identity = Identity::factory()->create(['user_id' => $user->id]);
        $this->assertInstanceOf(Identity::class, $user->identity);
        $this->assertInstanceOf(User::class, $identity->user);
    }

    /** @test */
    public function user_has_one_address()
    {
        $user = User::factory()->create();
        $address = Address::factory()->create(['user_id' => $user->id]);
        $this->assertInstanceOf(Address::class, $user->address);
        $this->assertInstanceOf(User::class, $address->user);
    }

    /** @test */
    public function user_has_one_company()
    {
        $user = User::factory()->create();
        $this->assertNull($user->company);
        $company = Company::factory()->create(['user_id' => $user->id]);

        $user->refresh();
        $this->assertInstanceOf(Company::class, $user->company);
        $this->assertInstanceOf(User::class, $company->user);
    }

    /** @test */
    public function user_has_many_recpients()
    {
        $user = User::factory()->create();
        Recipient::factory()->count(10)->create(['user_id' => $user->id]);

        $this->assertCount(10, $user->recipients);
        $this->assertInstanceOf(Collection::class, $user->recipients);
        $this->assertInstanceOf(Recipient::class, $user->recipients[0]);
    }

    /** @test */
    public function user_has_many_remitters()
    {
        $user = User::factory()->create();
        Remitter::factory()->count(10)->create(['user_id' => $user->id]);

        $this->assertCount(10, $user->remitters);
        $this->assertInstanceOf(Collection::class, $user->remitters);
        $this->assertInstanceOf(Remitter::class, $user->remitters[0]);
    }

    /** @test */
    public function an_user_can_get_pending_and_valid_status()
    {
        $user = User::factory()->create();
        $this->assertNull($user->identity);
        $this->assertNull($user->address);
        $this->assertEquals('SINFO', $user->status);

        $identity = Identity::factory()->create(['user_id' => $user->id]);
        $user->refresh();
        $this->assertNotNull($user->identity);
        $this->assertNull($user->identity->verified_at);
        $this->assertNull($user->address);
        $this->assertEquals('PENID', $user->status);

        $identity->verified_at = now();
        $identity->save();
        $user->refresh();
        $this->assertNotNull($user->identity);
        $this->assertNotNull($user->identity->verified_at);
        $this->assertNull($user->address);
        $this->assertEquals('VALID', $user->status);

        $address = Address::factory()->create(['user_id' => $user->id]);
        $user->refresh();
        $this->assertNotNull($user->identity);
        $this->assertNotNull($user->identity->verified_at);
        $this->assertNotNull($user->address);
        $this->assertNull($user->address->verified_at);
        $this->assertEquals('PENAD', $user->status);

        $address->verified_at = now();
        $address->save();
        $user->refresh();
        $this->assertNotNull($user->identity);
        $this->assertNotNull($user->identity->verified_at);
        $this->assertNotNull($user->address);
        $this->assertNotNull($user->address->verified_at);
        $this->assertEquals('VALAD', $user->status);
    }

    /** @test */
    public function an_user_can_get_rejected_identity_status()
    {
        $user = User::factory()->create();
        Identity::factory()->create([
            'user_id' => $user->id,
            'rejected_at' => now()
        ]);
        $this->assertNotNull($user->identity);
        $this->assertNotNull($user->identity->rejected_at);
        $this->assertNull($user->address);
        $this->assertEquals('REJID', $user->status);
    }

    /** @test */
    public function an_user_can_get_rejected_address_status()
    {
        $user = User::factory()->create();
        Identity::factory()->create([
            'user_id' => $user->id,
            'verified_at' => now()
        ]);
        Address::factory()->create([
            'user_id' => $user->id,
            'rejected_at' => now()
        ]);
        $this->assertNotNull($user->identity);
        $this->assertNotNull($user->address);
        $this->assertNotNull($user->address->rejected_at);
        $this->assertEquals('REJAD', $user->status);
    }
}
