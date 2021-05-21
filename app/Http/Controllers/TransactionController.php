<?php

namespace App\Http\Controllers;

use App\Http\Requests\TransactionRequest;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Account;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Models\AccountIncome;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\TransactionResource;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        if ($user->hasRole('admin')){
            $transaction = Transaction::paginate();
        } else {
            $transaction = Transaction::where('user_id', $user->id)->paginate();
        }
        return TransactionResource::collection($transaction);
    }

    public function show(Request $request, Transaction $transaction)
    {
        $user = Auth::user();
        if($user->hasRole('admin') || ($user->hasRole('user') && $user->id == $transaction->user_id)) {
            return new TransactionResource($transaction);
        }
        return response('Forbidden', Response::HTTP_FORBIDDEN);
    }

    public function store(TransactionRequest $request)
    {
        $request->validated();

        $account = Account::findOrFail($request->account_id);
        $user = User::findOrFail($request->user_id);

        $transaction = Transaction::create([
            'user_id' => $user->id,
            'account_id' => $account->id,
            'external_id' => $request->external_id,
            'type' => 'income',
            'amount' => $request->amount,
            'currency_id' => $account->currency_id,
            'note' => $request->note,
            'transaction_date' => Carbon::create($request->transaction_date)
        ]);

        AccountIncome::create([
            'account_id' => $request->account_id,
            'user_id' => $user->id,
            'transaction_id' => $transaction->id,
            'origin' => $user->identity ? $user->identity->identity_number : null,
            'transaction_number' => $request->external_id,
            'transaction_date' => Carbon::create($request->transaction_date),
            'amount' => $request->amount
        ]);

        $user->balance += $request->amount;
        $user->save();

        return response(new TransactionResource($transaction), Response::HTTP_CREATED);
    }

    public function reject(Transaction $transaction)
    {
        $user = User::findOrFail($transaction->user_id);
        $accountIncome = AccountIncome::where('transaction_id', $transaction->id)->firstOrFail();

        $transaction->rejected_at = now();
        $transaction->save();

        $user->balance -= $transaction->amount;
        $user->save();
        $accountIncome->rejected_at = now();
        $accountIncome->save();
        return new TransactionResource($transaction);
    }
}
