<?php

namespace Database\Seeders;

use App\Models\Currency;
use App\Models\Pair;
use Illuminate\Database\Seeder;

class PairSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $base = Currency::where('name', 'CLP')->first();
        $quote = Currency::where('name', 'COP')->first();
        Pair::factory()->create([
            'is_active'         => true,
            'is_default'        => true,
            'default_amount'    => 100000,
            'min_amount'        => 20000,
            'name'              => 'CLP/COP',
            'api_class'         => 'ExchangeRateApi',
            'observation'       => 'Pesos Chilenos a Pesos Colombianos.',
            'base_id'           => $base->id,
            'quote_id'          => $quote->id,
            'offset_by'         => 'percentage',
            'offset'            => 5,
            'offset_to_corps'   => 3,
            'offset_to_imports' => 1,
            'min_pip_value'     => 1,
            'show_inverse'      => false,
            'max_tier_1'        => 7000000,
            'max_tier_2'        => 50000000,
            'more_rate'         => 1,
            'is_more_enabled'   => false,
            'decimals'          => 2
        ]);
    }
}
