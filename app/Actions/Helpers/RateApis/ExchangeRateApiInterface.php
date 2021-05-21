<?php

namespace App\Actions\Helpers\RateApis;

use App\Models\Pair;

interface ExchangeRateApiInterface
{
    public function __construct(Pair $pair);

    public function getExchangeRate();

    public function fetchExchangeRate(string $source, string $currency);
}
