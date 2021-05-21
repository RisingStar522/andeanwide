<?php

namespace Tests\Unit;

use App\Models\Account;
use App\Models\Currency;
use App\Models\Order;
use Tests\TestCase;
use App\Models\User;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class TransactionTest extends TestCase
{
    use DatabaseMigrations, RefreshDatabase;

    /** @test */
    public function can_create_a_transaction()
    {
        Transaction::create([
            'user_id' => 1,
            'account_id' => 2,
            'order_id' => 3,
            'external_id' => '123456789',
            'type' => 'income',
            'amount' => 10000,
            'amount_usd' => 15,
            'currency_id' => 4,
            'note' => 'lorem ipsum',
            'rejected_at' => null,
            'transaction_date' => '01/01/2021 00:00:00'
        ]);

        $this->assertDatabaseHas('transactions', [
            'user_id' => 1,
            'account_id' => 2,
            'order_id' => 3,
            'external_id' => '123456789',
            'amount' => 10000,
            'amount_usd' => 15,
            'type' => 'income',
            'currency_id' => 4,
            'note' => 'lorem ipsum',
            'rejected_at' => null,
            'transaction_date' => '01/01/2021 00:00:00'
        ]);
    }

    /** @test */
    public function amount_type_must_be_income_or_outcome()
    {
        $transaction = Transaction::factory()->create(['type' => 'income']);
        $this->assertDatabaseHas('transactions', ['id' => $transaction->id, 'type' => 'income']);

        $transaction = Transaction::factory()->create(['type' => 'outcome']);
        $this->assertDatabaseHas('transactions', ['id' => $transaction->id, 'type' => 'outcome']);
    }

    /** @test */
    public function an_transaction_belongs_to_a_user()
    {
        $user = User::factory()->create();
        $transaction = Transaction::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $transaction->user);
    }

    /** @test */
    public function an_transaction_belongs_to_an_order()
    {
        $order = Order::factory()->create();
        $transaction = Transaction::factory()->create(['order_id' => $order->id]);

        $this->assertInstanceOf(Order::class, $transaction->order);
    }

    /** @test */
    public function an_transaction_belongs_to_an_account()
    {
        $account = Account::factory()->create();
        $transaction = Transaction::factory()->create(['account_id' => $account->id]);

        $this->assertInstanceOf(Account::class, $transaction->account);
    }

    /** @test */
    public function an_income_belongs_to_currency()
    {
        $currency = Currency::factory()->create();
        $transaction = Transaction::factory()->create(['currency_id' => $currency->id]);
        $this->assertInstanceOf(Currency::class, $transaction->currency);
    }
}
