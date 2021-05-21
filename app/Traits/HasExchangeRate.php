<?php

namespace App\Traits;

use App\Models\Pair;
use App\Traits\Strategies\CurrencyLayerApi;
use App\Traits\Strategies\YadioApi;
use App\Traits\Strategies\ExchangerateApi;

trait HasExchangeRate {

    protected $api;

    public function getExchangeRate()
    {
        if($this->api_class === 'ExchangeRateApi'){
            $api = new ExchangerateApi();
        } elseif ($this->api_class === "YadioApi") {
            $api = new YadioApi();
        } elseif ($this->api_class === "CurrencyLayerApi") {
            $api = new CurrencyLayerApi();
        } else {
            return [
                'error' => 'No se ha seleccionado ningún API para este par.'
            ];
        }
        return $api->performRequest($this);
    }
}
