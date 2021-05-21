<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Order;
use App\Models\Account;
use App\Models\Transaction;
use App\Actions\Helpers\TransactionAction;
use App\Models\AccountIncome;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class TransactionActionTest extends TestCase
{

    use DatabaseMigrations, RefreshDatabase;

    /** @test */
    public function can_create_an_order_transaction()
    {
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id]);
        $transaction = TransactionAction::createOrderTransaction($order);

        $this->assertNotNull($transaction);
        $this->assertInstanceOf(Transaction::class, $transaction);
        $this->assertEquals($user->id, $transaction->user_id);
        $this->assertEquals($order->id, $transaction->order_id);
        $this->assertEquals('outcome', $transaction->type);
        $this->assertNull($transaction->rejected_at);
        $this->assertDatabaseHas('transactions', [
            'user_id' => $user->id,
            'order_id' => $order->id,
            'type' => 'outcome',
            'amount' => $order->payment_amount,
        ]);
    }

    /** @test */
    public function createn_an_order_and_calculate_amount_in_usd()
    {
        $order = Order::factory()->create([
            'payment_amount' => 100000,
            'sended_amount'     => 91670,
            'received_amount'   => 192860,
            'rate'              => 2,
        ]);
        $transaction = TransactionAction::createOrderTransaction($order, 5);
        $this->assertNotNull($transaction->amount_usd);
        $this->assertEquals($transaction->amount_usd, 964300);

        $order = Order::factory()->create([
            'payment_amount' => 100000,
            'sended_amount'     => 91670,
            'received_amount'   => 192860,
            'rate'              => 2,
        ]);
        $transaction = TransactionAction::createOrderTransaction($order, 0.5);
        $this->assertNotNull($transaction->amount_usd);
        $this->assertEquals($transaction->amount_usd, 96430);
    }

    /** @test */
    public function can_create_an_income_transaction()
    {
        $user = User::factory()->create(['balance' => 0]);
        $account = Account::factory()->create();
        $transaction = TransactionAction::createIncomeTransaction($user, $account, 'abc123', 10000, 'note', now());

        $this->assertNotNull($transaction);
        $this->assertInstanceOf(Transaction::class, $transaction);
        $this->assertEquals($user->id, $transaction->user_id);
        $this->assertEquals($account->id, $transaction->account_id);
        $this->assertEquals('income', $transaction->type);
        $this->assertNull($transaction->rejected_at);

        $this->assertDatabaseHas('transactions', [
            'user_id' => $user->id,
            'account_id' => $account->id,
            'type' => 'income',
            'amount' => 10000,
            'external_id' => 'abc123',
            'note' => 'note'
        ]);

        $this->assertDatabaseHas('account_incomes', [
            'account_id' => $account->id,
            'user_id' => $user->id,
            'transaction_number' => 'abc123',
            'amount' => 10000
        ]);

        $user->refresh();
        $this->assertEquals(10000, $user->balance);
    }

    /** @test */
    public function cannot_create_an_income_transaction_if_amount_is_zero()
    {
        $user = User::factory()->create();
        $account = Account::factory()->create();
        $transaction = TransactionAction::createIncomeTransaction($user, $account, 'abc123', 0, 'note', now());

        $this->assertNull($transaction);
    }

    /** @test */
    public function cannot_create_an_income_transaction_if_external_id_is_an_empty_string()
    {
        $user = User::factory()->create();
        $account = Account::factory()->create();
        $transaction = TransactionAction::createIncomeTransaction($user, $account, '', 0, 'note', now());

        $this->assertNull($transaction);
    }

    /** @test */
    public function cannot_create_a_income_transcation_if_datetime_transaction_has_not_datetime_format()
    {
        $user = User::factory()->create();
        $account = Account::factory()->create();
        $transaction = TransactionAction::createIncomeTransaction($user, $account, '', 0, 'note', 'some text');

        $this->assertNull($transaction);
    }

    /** @test */
    public function can_reject_an_income_transaction()
    {
        $user = User::factory()->create(['balance' => 20000]);
        $account = Account::factory()->create();
        $transaction = TransactionAction::createIncomeTransaction($user, $account, 'abc123', 10000, 'note', now());

        $transaction = TransactionAction::rejectIncomeTransaction($transaction);

        $this->assertNotNull($transaction);
        $this->assertInstanceOf(Transaction::class, $transaction);
        $this->assertEquals($user->id, $transaction->user_id);
        $this->assertEquals($account->id, $transaction->account_id);
        $this->assertEquals('income', $transaction->type);
        $this->assertNotNull($transaction->rejected_at);

        $income = AccountIncome::where('transaction_id', $transaction->id)->first();
        $this->assertNotNull($income->rejected_at);

        $user->refresh();
        $this->assertEquals(20000, $user->balance);
    }
}
