<?php

namespace App\Actions\Helpers;

use App\Models\Pair;
use App\Models\Param;
use App\Actions\Helpers\RateApis\YadioApi;
use App\Actions\Helpers\RateApis\ExchangeRateApi;
use App\Actions\Helpers\RateApis\CurrencyLayerApi;
use App\Models\Currency;

class RateActions
{
    static public function getExchangeRate(Pair $pair=null, $base=null, $quote=null)
    {
        if ($pair) {
            if($pair->has_fixed_rate){
                if($pair)
                return (object) [
                    'api_rate'          => null,
                    'bid'               => (float) $pair->personal_fixed_rate,
                    'bid_to_corps'      => (float) $pair->corps_fixed_rate,
                    'bid_to_imports'    => (float) $pair->imports_fixed_rate,
                    'updated_at'        => $pair->updated_at,
                    'created_at'        => $pair->updated_at,
                ];
            } else {
                if($pair->api_class === 'ExchangeRateApi'){
                    $api = new ExchangerateApi($pair);
                } elseif ($pair->api_class === "YadioApi") {
                    $api = new YadioApi($pair);
                } elseif ($pair->api_class === "CurrencyLayerApi") {
                    $api = new CurrencyLayerApi($pair);
                } else {
                    return [
                        'error' => 'Not valid api.'
                    ];
                }
                return $api->getExchangeRate();
            }
        } else {
            $api_class = Param::where('name', 'defaultRateApi')->first();
            if($api_class->value == 'CurrencyLayerApi') {
                $api = new CurrencyLayerApi(null);
            } else if($api_class->value == 'ExchangeRateApi'){
                $api = new ExchangeRateApi(null);
            } else if($api_class->value == 'YadioApi'){
                $api = new YadioApi(null);
            }
            return $api->fetchExchangeRate($base, $quote);
        }
    }
}
