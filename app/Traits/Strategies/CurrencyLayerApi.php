<?php

namespace App\Traits\Strategies;

use Carbon\Carbon;
use App\Models\Pair;
use App\Models\Rate;
use Illuminate\Support\Facades\Http;
use App\Traits\Strategies\ApiInterface;

class CurrencyLayerApi implements ApiInterface
{
    public function performRequest(Pair $pair)
    {
        if (is_null($pair->rate)) {
            $this->getRate($pair);
            $pair->fresh();
        }
        $lastRate = $pair->rate;
        $rate = $lastRate->quote;
        if ($pair) {
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
                'created_at'        => $lastRate->created_at,
                'api_updated_at'    => $lastRate->api_timestamp,
            ];
        }

        return (object)[
            'api_rate'          => null,
            'bid'               => null,
            'bid_to_corps'      => null,
            'bid_to_imports'    => null,
            'created_at'        => null,
            'api_updated_at'    => null,
        ];
    }

    public function getRate(Pair $pair)
    {
        $source = $pair->base->symbol;
        $currency = $pair->quote->symbol;
        $response = Http::get(config('services.exchange_api.currencylayer.url') . '?access_key=' . config('services.exchange_api.currencylayer.key') . '&currencies=' . $currency . '&source=' . $source . '&format=1');
        if($response->successful())
        {
            $body = $response->json();
            if($body["success"]) {
                $rate = Rate::create([
                    'base_currency_id' => $pair->base->id,
                    'quote_currency_id' => $pair->quote->id,
                    'pair_id' => $pair->id,
                    'pair_name' => $pair->name,
                    'quote' => $body['quotes'][$source . $currency],
                    'api_timestamp' => Carbon::createFromTimestamp($body['timestamp']),
                ]);
            }
            return $rate;
        }
        return null;
    }
}
