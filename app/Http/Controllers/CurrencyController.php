<?php

namespace App\Http\Controllers;

use App\Http\Resources\CurrencyResource;
use App\Models\Currency;
use Illuminate\Http\Request;

class CurrencyController extends Controller
{
    public function index()
    {
        $currencies = Currency::where('is_active', true)->get();
        return CurrencyResource::collection($currencies);
    }

    public function indexAll()
    {
        $currencies = Currency::paginate();
        return CurrencyResource::collection($currencies);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'          => 'required',
            'symbol'        => 'required',
            'country_id'    => 'required|exists:countries,id'
        ]);

        $currency = Currency::create([
            'name'          => $request->input('name'),
            'symbol'        => $request->input('symbol'),
            'is_active'     => $request->input('is_active', true),
            'can_send'      => $request->input('can_send', true),
            'can_receive'   => $request->input('can_receive', true),
            'country_id'    => $request->input('country_id'),
        ]);

        return new CurrencyResource($currency);
    }

    public function show(Currency $currency)
    {
        return new CurrencyResource($currency);
    }

    public function update(Request $request, Currency $currency)
    {
        $request->validate([
            'name'          => "required",
            'symbol'        => "required",
            'country_id'    => 'required|exists:countries,id'
        ]);

        $currency->name = $request->input("name");
        $currency->symbol = $request->input("symbol");
        $currency->country_id = $request->input("country_id");
        $currency->is_active = $request->input("is_active", true);
        $currency->can_send = $request->input("can_send", true);
        $currency->can_receive = $request->input("can_receive", true);
        $currency->save();

        return new CurrencyResource($currency);
    }

    public function destroy(Currency $currency)
    {
        $currency->delete();
        return new CurrencyResource($currency);
    }
}
