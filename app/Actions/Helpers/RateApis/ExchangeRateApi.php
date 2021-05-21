<?php

namespace App\Actions\Helpers\RateApis;

use App\Models\Pair;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

class ExchangeRateApi implements ExchangeRateApiInterface
{
    protected $pair;
    protected $api_uri;
    protected $api_key;

    public function __construct(Pair $pair = null)
    {
        $this->api_uri = config('services.exchange_api.exchangerate.url');
        $this->api_key = config('services.exchange_api.exchangerate.key');
        $this->pair = $pair;
    }

    public function getExchangeRate()
    {
        try {
            $response = Http::get($this->api_uri . $this->api_key . "/latest" . "/" . $this->pair->base->symbol);;
            if($response->successful() && $response->json()){
                $rates = $response->json();
                $rate = $rates['conversion_rates'][$this->pair->quote->symbol];
                if($this->pair->offset_by === 'point') {
                    $bid = $rate + $this->pair->offset * $this->pair->min_pip_value;
                    $bidToCorps = $rate + $this->pair->offset_to_corps * $this->pair->min_pip_value;
                    $bidToImports = $rate + $this->pair->offset_to_imports * $this->pair->min_pip_value;
                } else {
                    $bid = $rate * ( 1 + $this->pair->offset/100 );
                    $bidToCorps = $rate * ( 1 + $this->pair->offset_to_corps/100 );
                    $bidToImports = $rate * ( 1 + $this->pair->offset_to_imports/100 );
                }
                return (object)[
                    'api_rate' => (float) $rate,
                    'bid' => (float) $bid,
                    'bid_to_corps' => (float) $bidToCorps,
                    'bid_to_imports' => (float) $bidToImports,
                    'created_at' => Carbon::createFromTimestamp($rates['time_last_update_unix']),
                    'updated_at' => Carbon::createFromTimestamp($rates['time_last_update_unix'])
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

    public function fetchExchangeRate(string $source, string $currency)
    {
        try {
            $response = Http::get($this->api_uri . $this->api_key . "/latest" . "/" . $source);
            if($response->successful()){
                $data = $response->json();
                return $data['conversion_rates'][$currency];
            }
            return null;
        } catch (\Throwable $th) {
            return null;
        }

    }
}
