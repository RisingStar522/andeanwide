<?php

namespace Database\Factories;

use App\Models\Country;
use Illuminate\Database\Eloquent\Factories\Factory;

class CountryFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Country::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name'          => $this->faker->country(),
            'abbr'          => $this->faker->countryCode(),
            'currency'      => $this->faker->currencyCode(),
            'currency_code' => $this->faker->currencyCode(),
        ];
    }
}
