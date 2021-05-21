<?php

namespace App\Http\Controllers;

use App\Http\Resources\AccountIncomeResource;
use App\Models\Account;
use App\Models\AccountIncome;
use Illuminate\Http\Request;

class AccountIncomeController extends Controller
{
    public function index(Account $account)
    {
        $accountIncomes = AccountIncome::where('account_id', $account->id)->orderBy('created_at', 'desc')->paginate();
        return AccountIncomeResource::collection($accountIncomes);
    }

    public function store(Request $request, Account $account)
    {
        $accountIncome = $account->incomes()->create([
            'user_id' => $request->user_id,
            'origin' => $request->origin,
            'transaction_number' => $request->transaction_number,
            'transaction_date' => $request->transaction_date,
            'amount' => $request->amount,
        ]);
        return new AccountIncomeResource($accountIncome);
    }
}
