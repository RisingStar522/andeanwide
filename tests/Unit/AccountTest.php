<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Bank;
use App\Models\Account;
use App\Models\AccountIncome;
use App\Models\Country;
use App\Models\Currency;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class AccountTest extends TestCase
{
    use DatabaseMigrations, RefreshDatabase;

    /** @test */
    public function can_create_an_account()
    {
        $account = Account::create([
            'is_active' => true,
            'country_id' => 1,
            'currency_id' => 2,
            'bank_id' => 3,
            'label' => 'label',
            'bank_name' => 'bank_name',
            'bank_account' => '1234-1234-1234-1234',
            'account_name' => 'name',
            'account_type' => 'account type',
            'description' => 'account test',
            'document_number' => '123345678'
        ]);

        $this->assertInstanceOf(Account::class, $account);
        $this->assertDatabaseHas('accounts', [
            'id' => $account->id,
            'is_active' => true,
            'country_id' => 1,
            'currency_id' => 2,
            'bank_id' => 3,
            'label' => 'label',
            'bank_name' => 'bank_name',
            'bank_account' => '1234-1234-1234-1234',
            'account_name' => 'name',
            'description' => 'account test',
            'account_type' => 'account type',
            'document_number' => '123345678'
        ]);
    }

    /** @test */
    public function an_account_belongs_to_a_country()
    {
        $country = Country::factory()->create();
        $account = Account::factory()->create([
            'country_id' => $country->id
        ]);

        $this->assertInstanceOf(Country::class, $account->country);
    }

    /** @test */
    public function an_account_belongs_to_a_bank()
    {
        $bank = Bank::factory()->create();
        $account = Account::factory()->create([
            'bank_id' => $bank->id
        ]);

        $this->assertInstanceOf(Bank::class, $account->bank);
    }

    /** @test */
    public function payment_has_a_account()
    {
        $account = Account::factory()->create();
        $payment = Payment::factory()->create([
            'account_id' => $account->id
        ]);

        $this->assertInstanceOf(Account::class, $payment->account);
    }

    /** @test */
    public function account_belongs_to_a_currency()
    {
        $currency = Currency::factory()->create();
        $account = Account::factory()->create([
            'currency_id' => $currency->id
        ]);

        $this->assertInstanceOf(Currency::class, $account->currency);
    }

    /** @test */
    public function account_has_many_account_movement()
    {
        $account = Account::factory()->create();
        $account_incomes = AccountIncome::factory()->count(10)->create([
            'account_id' => $account->id
        ]);

        $this->assertInstanceOf(Collection::class, $account->incomes);
        $this->assertInstanceOf(AccountIncome::class, $account->incomes[0]);
        $this->assertCount(10, $account->incomes);
    }

    /** @test */
    public function can_add_secret_key_to_account()
    {
        $account = Account::factory()->create();
        $account->secret_key = 'abc123';
        $account->save();

        $account->refresh();
        $this->assertNotNull($account->secret_key);
        $this->assertEquals('abc123', $account->secret_key);
    }
}
