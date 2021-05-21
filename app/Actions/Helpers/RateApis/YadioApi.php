<?php

namespace App\Actions\Helpers\RateApis;

use App\Models\Pair;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

class YadioApi implements ExchangeRateApiInterface
{
    protected $url;
    protected $pair;

    public function __construct(Pair $pair = null)
    {
        $this->pair = $pair;
        $this->url = config('services.exchange_api.yadio.url');
    }

    public function getExchangeRate()
    {
        try {
            $response = Http::get($this->url . '/exrates' . "/" . $this->pair->base->symbol);
            if($response->successful()){
                $rates = $response->json();
                $rate = $rates[$this->pair->base->symbol][$this->pair->quote->symbol];
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
                    'created_at' => Carbon::createFromTimestamp($rates['timestamp']),
                    'updated_at' => Carbon::createFromTimestamp($rates['timestamp'])
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

    public function fetchExchangeRAte(string $source, string $currency)
    {
        try {
            $response = Http::get($this->url . '/exrates' . "/" . $source);
            if($response->successful()){
                $data = $response->json();
                return $data[$source][$currency];
            }
            return null;
        } catch (\Throwable $th) {
            return null;
        }
        return null;
    }
}
