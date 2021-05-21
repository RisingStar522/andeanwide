<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Account;
use App\Models\AccountIncome;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class AccountIncomeTest extends TestCase
{
    use DatabaseMigrations, RefreshDatabase;

    /** @test */
    public function can_create_an_income()
    {
        AccountIncome::create([
            'account_id' => 1,
            'user_id' => 1,
            'transaction_id' => 1,
            'origin' => '123456789',
            'transaction_number' => 1,
            'transaction_date' => '01/01/2021 00:00:00',
            'amount' => 10000
        ]);

        $this->assertDatabaseHas('account_incomes', [
            'account_id' => 1,
            'user_id' => 1,
            'transaction_id' => 1,
            'transaction_number' => 1,
            'transaction_date' => '01/01/2021 00:00:00',
            'amount' => 10000,
            'rejected_at' => null
        ]);
    }

    /** @test */
    public function account_income_belongs_to_an_account()
    {
        $account = Account::factory()->create();
        $income = AccountIncome::factory()->create([
            'account_id' => $account->id,
        ]);

        $this->assertInstanceOf(Account::class, $income->account);
        $this->assertInstanceOf(Collection::class, $account->incomes);
        $this->assertInstanceOf(AccountIncome::class, $account->incomes[0]);
        $this->assertEquals($income->account_id, $account->id);
    }

    /** @test */
    public function account_income_belongs_to_an_user()
    {
        $user = User::factory()->create();
        $income = AccountIncome::factory()->create([
            'user_id' => $user->id,
        ]);

        $this->assertInstanceOf(User::class, $income->user);
        $this->assertEquals($income->user_id, $user->id);
    }
}
