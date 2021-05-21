<?php

namespace Database\Seeders;

use App\Models\Currency;
use Illuminate\Database\Seeder;

class CurrencySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Currency::factory()->create([
            'name'          => 'CLP',
            'symbol'        => 'CLP',
            'country_id'    => 28,
        ]);
        Currency::factory()->create([
            'name'          => 'COP',
            'symbol'        => 'COP',
            'country_id'    => 30
        ]);
    }
}
