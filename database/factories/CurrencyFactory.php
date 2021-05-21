<?php

namespace Database\Factories;

use App\Models\Country;
use App\Models\Currency;
use Illuminate\Database\Eloquent\Factories\Factory;

class CurrencyFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Currency::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name'          => $this->faker->currencyCode(),
            'symbol'        => $this->faker->currencyCode(),
            'country_id'    => Country::factory()->create(),
            'is_active'     => true,
            'can_send'      => true,
            'can_receive'   => true,
        ];
    }
}
