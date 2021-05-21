<?php

namespace App\Traits\Strategies;

use App\Models\Pair;
use App\Traits\Strategies\ApiInterface;
use GuzzleHttp\Client;

class YadioApi implements ApiInterface
{
    protected $api_uri;

    public function __construct()
    {
        $this->api_uri = config('services.exchange_api.yadio.url');
    }

    public function performRequest(Pair $pair)
    {
        try {
            $client = new Client([
                'base_uri'  => $this->api_uri
            ]);

            $response = null;

            if($pair->quote->symbol === 'VEF'){
                $response = $client->request('GET', $pair->base->symbol);
            } else if($pair->base->symbol === 'VEF'){
                $response = $client->request('GET', $pair->quote->symbol);
            }
            else {
                return [
                    'error' => 'No results'
                ];
            }

            $response = $response->getBody()->getContents();
            $rates = json_decode($response, true);

            if(isset($rates['rate'])){
                $rate = $rates['rate'];
                if($pair->base->symbol === 'VEF') $rate = 1/$rate;

                if($pair->offset_by == 'point') {
                    $bid = $rate + $pair->offset * $pair->min_pip_value;
                    $bidToCorps = $rate + $pair->offset_to_corps * $pair->min_pip_value;
                    $bidToImports = $rate + $pair->offset_to_imports * $pair->min_pip_value;
                } else {
                    $bid = $rate * ( 1 + $pair->offset/100 );
                    $bidToCorps = $rate * ( 1 + $pair->offset/100 );
                    $bidToImports = $rate * ( 1 + $pair->offset/100 );
                }

                return (object) [
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
