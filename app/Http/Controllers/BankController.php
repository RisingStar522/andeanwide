<?php

namespace App\Http\Controllers;

use App\Models\Bank;
use App\Models\Country;
use Illuminate\Http\Request;
use App\Http\Resources\BankResource;

class BankController extends Controller
{
    public function index()
    {
        $banks = Bank::paginate();
        return BankResource::collection($banks);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'          => 'required',
            'country_id'    => 'required|exists:countries,id',
            'code'          => 'required'
        ]);

        $bank = Bank::create([
            'name'          => $request->name,
            'country_id'    => $request->country_id,
            'abbr'          => $request->abbr,
            'code'          => $request->code,
            'is_active'     => true
        ]);

        return new BankResource($bank);
    }

    public function show(Bank $bank)
    {
        return new BankResource($bank);
    }

    public function update(Request $request, Bank $bank)
    {
        $request->validate([
            'name'          => 'required',
            'country_id'    => 'required|exists:countries,id',
            'code'          => 'required'
        ]);

        $bank->name = $request->input('name', $bank->name);
        $bank->country_id = $request->input('country_id', $bank->country_id);
        $bank->abbr = $request->input('abbr', $bank->abbr);
        $bank->code = $request->input('code', $bank->code);

        return new BankResource($bank);
    }

    public function activate(Bank $bank)
    {
        $bank->is_active = true;
        $bank->save();
        return new BankResource($bank);
    }

    public function deactivate(Bank $bank)
    {
        $bank->is_active = false;
        $bank->save();
        return new BankResource($bank);
    }

    public function indexByCountry(Country $country)
    {
        $banks = $country->banks;
        return BankResource::collection($banks);
    }
}
