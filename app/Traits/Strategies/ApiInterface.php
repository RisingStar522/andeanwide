<?php

namespace App\Traits\Strategies;

use App\Models\Pair;

interface ApiInterface
{
    public function performRequest(Pair $pair);
}
