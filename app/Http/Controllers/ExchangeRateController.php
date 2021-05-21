<?php

namespace App\Http\Controllers;

use App\Actions\Helpers\RateActions;
use App\Http\Resources\ExchangeRateResource;
use App\Models\Pair;
use App\Traits\HasExchangeRate;

class ExchangeRateController extends Controller
{

    use HasExchangeRate;

    public function getRate($base, $quote)
    {

        $pair = Pair::where('name', "$base/$quote")->first();
        if($pair){
            try {
                return new ExchangeRateResource(RateActions::getExchangeRate($pair));
            } catch (\Throwable $th) {
                return response([
                    'error' => 'Ups! Something went wrong',
                ], 500);
            }
        }
        return response([
            'error' => 'No results'
        ], 404);
    }
}
