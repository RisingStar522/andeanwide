<?php

namespace App\Http\Controllers;

use App\Models\Account;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Resources\AccountResource;
use Illuminate\Http\Response;

class AccountController extends Controller
{
    public function index(Request $request)
    {
        $accountsQuery = Account::where('is_active', true);
        foreach ($request->query() as $key => $value) {
            $accountsQuery->where($key, $value);
        }
        return AccountResource::collection($accountsQuery->get());
    }

    public function adminIndex()
    {
        return AccountResource::collection(Account::paginate());
    }

    public function store(Request $request)
    {
        $request->validate([
            'country_id' => 'required|exists:countries,id',
            'currency_id' => 'required|exists:currencies,id',
            'bank_id' => 'nullable|exists:banks,id',
            'label' => 'required',
            'bank_account' => 'required',
            'account_name' => 'required',
            'document_number' => 'required'
        ]);

        $account = Account::create([
            'is_active' => true,
            'country_id' => $request->input('country_id'),
            'currency_id' => $request->input('currency_id'),
            'bank_id' => $request->input('bank_id'),
            'label' => $request->input('label'),
            'bank_name' => $request->input('bank_name'),
            'bank_account' => $request->input('bank_account'),
            'account_name' => $request->input('account_name'),
            'description' => $request->input('description'),
            'document_number' => $request->input('document_number'),
            'account_type' => $request->input('account_type'),
        ]);

        return new AccountResource($account);
    }

    public function show(Account $account)
    {
        return new AccountResource($account);
    }

    public function update(Request $request, Account $account)
    {
        $request->validate([
            'currency_id' => 'required|exists:currencies,id',
            'bank_id' => 'nullable|exists:banks,id',
            'label' => 'required',
            'bank_account' => 'required',
            'account_name' => 'required',
            'document_number' => 'required'
        ]);

        $account->is_active = $request->input('is_active', true);
        $account->currency_id = $request->input('currency_id', $account->currency_id);
        $account->bank_id = $request->input('bank_id', $account->bank_id);
        $account->label = $request->input('label', $account->label);
        $account->bank_name = $request->input('bank_name', $account->bank_name);
        $account->bank_account = $request->input('bank_account', $account->bank_account);
        $account->account_name = $request->input('account_name', $account->account_name);
        $account->description = $request->input('description', $account->description);
        $account->document_number = $request->input('document_number', $account->document_number);
        $account->account_type = $request->input('account_type', $account->account_type);
        $account->save();

        return new AccountResource($account);
    }

    public function destroy(Account $account)
    {
        //
    }

    public function createSecretKey(Account $account)
    {
        $account->secret_key = Str::random(100);
        $account->save();
        return response(['data' => ['secret_key' => $account->secret_key]], Response::HTTP_CREATED);
    }

    /** @test */
    public function getSecretKey(Account $account)
    {
        return response(['data' => ['secret_key' => $account->secret_key]], Response::HTTP_OK);
    }
}
