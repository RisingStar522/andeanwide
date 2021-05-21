<?php

namespace App\Actions\Helpers\RateApis;

use Carbon\Carbon;
use App\Models\Pair;
use App\Models\Rate;
use Illuminate\Support\Facades\Http;

class CurrencyLayerApi implements ExchangeRateApiInterface
{
    protected $base_url;
    protected $key;

    public function __construct(Pair $pair = null)
    {
        $this->pair = $pair;
        $this->base_url = config('services.exchange_api.currencylayer.url');
        $this->key = config('services.exchange_api.currencylayer.key');
    }

    public function getExchangeRate()
    {
        try {
            if (is_null($this->pair->rate)) {
                $this->performRequest();
                $this->pair->fresh();
            }
            $lastRate = $this->pair->rate;
            $rate = $lastRate->quote;
            if($this->pair->offset_by == 'point') {
                $bid = $rate + $this->pair->offset * $this->pair->min_pip_value;
                $bidToCorps = $rate + $this->pair->offset_to_corps * $this->pair->min_pip_value;
                $bidToImports = $rate + $this->pair->offset_to_imports * $this->pair->min_pip_value;
            } else {
                $bid = $rate * ( 1 + $this->pair->offset/100 );
                $bidToCorps = $rate * ( 1 + $this->pair->offset_to_corps/100 );
                $bidToImports = $rate * ( 1 + $this->pair->offset_to_imports/100 );
            }

            return (object)[
                'api_rate'          => (float) $rate,
                'bid'               => (float) $bid,
                'bid_to_corps'      => (float) $bidToCorps,
                'bid_to_imports'    => (float) $bidToImports,
                'created_at'        => $lastRate->created_at,
                'updated_at'        => $lastRate->api_timestamp,
            ];
        } catch (\Throwable $th) {
            return [
                'error' => 'No results'
            ];
        }
    }

    public function performRequest()
    {
        $source = $this->pair->base->symbol;
        $currency = $this->pair->quote->symbol;
        $response = Http::get($this->base_url . '?access_key=' . $this->key . '&currencies=' . $currency . '&source=' . $source . '&format=1');
        if($response->successful())
        {
            $data = $response->json();
            if($data["success"]) {
                $rate = Rate::create([
                    'base_currency_id' => $this->pair->base->id,
                    'quote_currency_id' => $this->pair->quote->id,
                    'pair_id' => $this->pair->id,
                    'pair_name' => $this->pair->name,
                    'quote' => $data['quotes'][$source . $currency],
                    'api_timestamp' => Carbon::createFromTimestamp($data['timestamp']),
                ]);
                return $rate;
            }
        }
        return null;
    }

    public function fetchExchangeRate(string $source, string $currency)
    {
        try {
            $response = Http::get($this->base_url . '?access_key=' . $this->key . '&currencies=' . $currency . '&source=' . $source . '&format=1');
            if($response->successful())
            {
                $data = $response->json();
                return $data['quotes'][$source . $currency];
            }
        } catch (\Throwable $th) {
            return null;
        }
        return null;
    }
}
