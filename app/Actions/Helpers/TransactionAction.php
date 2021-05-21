<?php

namespace App\Actions\Helpers;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Order;
use App\Models\Account;
use App\Models\Transaction;
use Illuminate\Support\Str;
use App\Models\AccountIncome;
use Illuminate\Support\Facades\DB;

class TransactionAction
{
    static public function createOrderTransaction(Order $order, $usdRate=1)
    {
        $transaction = Transaction::create([
            'user_id' => $order->user_id,
            'order_id' => $order->id,
            'external_id' => 'AW' . Str::padLeft($order->id, 6, '0'),
            'type' => 'outcome',
            'amount' => $order->payment_amount,
            'amount_usd' => $order->received_amount * $usdRate,
            'currency_id' => $order->pair->base->id,
            'note' => '',
            'rejected_at' => null,
            'transaction_date' => now()
        ]);

        return $transaction;
    }

    static public function createIncomeTransaction(User $user, Account $account, string $external_id, float $amount, string $note, string $transaction_date)
    {
        if (
            isset($user) &&
            isset($account) &&
            isset($external_id) &&
            isset($amount) &&
            $amount>0 &&
            isset($note) &&
            isset($transaction_date)
        ) {
            $transaction = DB::transaction(function () use ($user, $account, $external_id, $amount, $note, $transaction_date){
                $transaction = Transaction::create([
                    'user_id' => $user->id,
                    'account_id' => $account->id,
                    'external_id' => $external_id,
                    'type' => 'income',
                    'amount' => $amount,
                    'currency_id' => $account->currency_id,
                    'note' => $note,
                    'transaction_date' => Carbon::create($transaction_date)
                ]);

                AccountIncome::create([
                    'account_id' => $account->id,
                    'user_id' => $user->id,
                    'transaction_id' => $transaction->id,
                    'origin' => $user->identity ? $user->identity->identity_number : null,
                    'transaction_number' => $external_id,
                    'transaction_date' => Carbon::create($transaction_date),
                    'amount' => $amount
                ]);

                $user->balance += $amount;
                $user->save();

                return $transaction;
            });
            return $transaction;
        }
        return null;
    }

    static public function rejectIncomeTransaction($transaction)
    {
        if(is_null($transaction->rejected_at)){
            $transaction = DB::transaction(function () use($transaction) {
                $accountIncome = AccountIncome::where('transaction_id', $transaction->id)->firstOrFail();
                $transaction->rejected_at = now();
                $transaction->save();

                $accountIncome->rejected_at = now();
                $accountIncome->save();

                $user = User::findOrFail($transaction->user_id);
                $user->balance -= $transaction->amount;
                $user->save();

                $transaction->fresh();
                return $transaction;
            });
            return $transaction;
        }
        return null;
    }
}
