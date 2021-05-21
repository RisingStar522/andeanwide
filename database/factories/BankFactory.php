<?php

namespace Database\Factories;

use App\Models\Bank;
use App\Models\Country;
use Illuminate\Database\Eloquent\Factories\Factory;

class BankFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Bank::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'country_id'    => Country::factory()->create(),
            'name'          => $this->faker->company(),
            'abbr'          => $this->faker->lexify('???'),
            'code'          => $this->faker->numerify('###'),
            'is_active'     => true
        ];
    }
}
