<?php

namespace App\Traits\Strategies;

use App\Models\Pair;
use GuzzleHttp\Client;
use App\Traits\Strategies\ApiInterface;

class ExchangerateApi implements ApiInterface
{
    protected $api_uri;
    protected $api_key;

    public function __construct()
    {
        $this->api_uri = config('services.exchange_api.exchangerate.url');
        $this->api_key = config('services.exchange_api.exchangerate.key');
    }

    public function performRequest(Pair $pair)
    {
        try {
            $base_url = $this->api_uri . $this->api_key . "/" . "latest" . "/" . $pair->base->symbol;

            $client = new Client([
                'base_uri' => $base_url
            ]);

            $response = $client->request('GET');
            $response = $response->getBody()->getContents();

            $rates = json_decode($response, true);

            if(isset($rates['conversion_rates']) && isset($rates['conversion_rates'][$pair->quote->symbol])){
                $rate = $rates['conversion_rates'][$pair->quote->symbol];
                if($pair->offset_by == 'point') {
                    $bid = $rate + $pair->offset * $pair->min_pip_value;
                    $bidToCorps = $rate + $pair->offset_to_corps * $pair->min_pip_value;
                    $bidToImports = $rate + $pair->offset_to_imports * $pair->min_pip_value;
                } else {
                    $bid = $rate * ( 1 + $pair->offset/100 );
                    $bidToCorps = $rate * ( 1 + $pair->offset_to_corps/100 );
                    $bidToImports = $rate * ( 1 + $pair->offset_to_imports/100 );
                }

                return (object)[
                    'api_rate'          => $rate,
                    'bid'               => $bid,
                    'bid_to_corps'      => $bidToCorps,
                    'bid_to_imports'    => $bidToImports,
                ];
            }
            return [
                'error' => 'No results'
            ];
        } catch (\Throwable $th) {
            return [
                'error' => 'No results'
            ];
        }
    }
}
