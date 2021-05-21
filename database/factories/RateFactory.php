<?php

namespace Database\Factories;

use App\Models\Currency;
use App\Models\Pair;
use App\Models\Rate;
use Illuminate\Database\Eloquent\Factories\Factory;

class RateFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Rate::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $base = Currency::factory()->create();
        $quote = Currency::factory()->create();
        return [
            'base_currency_id' => $base->id,
            'quote_currency_id' => $quote->id,
            'pair_id' => Pair::factory()->create(['base_id' => $base->id, 'quote_id' => $quote->id]),
            'pair_name' => $this->faker->currencyCode(),
            'quote' => 1,
            'api_timestamp' => now()
        ];
    }
}
