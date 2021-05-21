<?php

namespace App\Actions\Helpers;

use stdClass;
use App\Models\Pair;
use App\Models\Currency;
use App\Models\Param;

class OrderAction
{
    public static function calculateCosts($payment_amount, $transaction_pct, $tax, $priority_pct)
    {
        $result = new stdClass();
        $result->transaction_cost = $payment_amount * $transaction_pct / 100;
        $result->priority_cost = $payment_amount * $priority_pct / 100;
        $result->total_cost = $result->transaction_cost + $result->priority_cost;
        $result->tax_cost = $result->total_cost * $tax / 100;
        $result->amount_to_send = $payment_amount - $result->total_cost - $result->tax_cost;
        return $result;
    }

    public static function calculateAmountToReceive($amount_to_send, $rate)
    {
        return $amount_to_send * $rate;
    }

    public static function validateRate(Pair $pair, float $rate, string $account_type)
    {
        $rate_from_api = RateActions::getExchangeRate($pair);
        if($pair->show_inverse){
            $rate_from_api->api_rate = 1/$rate_from_api->api_rate;
            $rate_from_api->bid = 1/$rate_from_api->bid;
            $rate_from_api->bid_to_corps = 1/$rate_from_api->bid_to_corps;
            $rate_from_api->bid_to_imports = 1/$rate_from_api->bid_to_imports;
        }

        $variance = $pair->min_pip_value;
        if($account_type=='personal' && (($rate_from_api->bid + $variance) > $rate) && (($rate_from_api->bid - $variance) < $rate)){
            return true;
        } else if ($account_type=='corporative' && (($rate_from_api->bid_to_corps + $variance) > $rate) && (($rate_from_api->bid_to_corps - $variance) < $rate)) {
            return true;
        } else if ($account_type=='imports' && (($rate_from_api->bid_to_imports + $variance) > $rate) && (($rate_from_api->bid_to_imports - $variance) < $rate)) {
            return true;
        }
        return false;
    }

    public static function convertAmountToUsd(string $currency, float $amount)
    {
        $rate = RateActions::getExchangeRate(null, 'USD', $currency);
        if($rate) {
            return $rate * $amount;
        }
        return null;
    }
}
