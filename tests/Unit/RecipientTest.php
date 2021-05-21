<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Bank;
use App\Models\User;
use App\Models\Country;
use App\Models\Recipient;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class RecipientTest extends TestCase
{
    use DatabaseMigrations, RefreshDatabase, WithFaker;

    /** @test */
    public function can_create_a_recipient()
    {
        $recipient = Recipient::create([
            'user_id'       => 1,
            'country_id'    => 1,
            'bank_id'       => 1,
            'name'          => 'name',
            'lastname'      => 'lastname',
            'dni'           => '12345667',
            'document_type' => 'PASS',
            'phone'         => '+123456568',
            'email'         => 'name@email.com',
            'bank_name'     => 'bank_account',
            'bank_account'  => '1283709481',
            'account_type'  => 'S',
            'bank_code'     => 'aoisdjk10',
            'address'       => 'Av. Falsa 123, FAKE'
        ]);

        $this->assertInstanceOf(Recipient::class, $recipient);
        $this->assertDatabaseHas('recipients', [
            'user_id'       => 1,
            'country_id'    => 1,
            'bank_id'       => 1,
            'name'          => 'name',
            'lastname'      => 'lastname',
            'dni'           => '12345667',
            'document_type' => 'PASS',
            'phone'         => '+123456568',
            'email'         => 'name@email.com',
            'bank_name'     => 'bank_account',
            'bank_account'  => '1283709481',
            'account_type'  => 'S',
            'bank_code'     => 'aoisdjk10',
            'address'       => 'Av. Falsa 123, FAKE'
        ]);
    }

    /** @test */
    public function a_recipient_belongs_to_a_country()
    {
        $country = Country::factory()->create();
        $recipient = Recipient::factory()->create([
            'country_id' => $country->id,
        ]);

        $this->assertInstanceOf(Country::class, $recipient->country);
    }

    /** @test */
    public function a_recipient_belongs_to_an_user()
    {
        $user = User::factory()->create();
        $recipient = Recipient::factory()->create([
            'user_id' => $user->id,
        ]);

        $this->assertInstanceOf(User::class, $recipient->user);
        $this->assertInstanceOf(Collection::class, $user->recipients);
        $this->assertInstanceOf(Recipient::class, $user->recipients[0]);
    }

    /** @test */
    public function a_recipient_has_a_bank()
    {
        $bank = Bank::factory()->create();
        $recipient = Recipient::factory()->create([
            'bank_id' => $bank->id,
        ]);

        $this->assertInstanceOf(Bank::class, $recipient->bank);
    }
}
