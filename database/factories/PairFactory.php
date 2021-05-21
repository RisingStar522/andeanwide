<?php

namespace Database\Factories;

use App\Models\Pair;
use App\Models\Currency;
use Illuminate\Database\Eloquent\Factories\Factory;

class PairFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Pair::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'is_active'         => true,
            'is_default'        => false,
            'default_amount'    => 100000,
            'min_amount'        => 10000,
            'name'              => $this->faker->word(),
            'api_class'         => $this->faker->word(),
            'observation'       => $this->faker->sentence(),
            'base_id'           => Currency::factory()->create(),
            'quote_id'          => Currency::factory()->create(),
            'offset_by'         => 'percentage',
            'offset'            => 3,
            'offset_to_corps'   => 2,
            'offset_to_imports' => 1,
            'min_pip_value'     => 1,
            'show_inverse'      => false,
            'max_tier_1'        => 1000000,
            'max_tier_2'        => 5000000,
            'more_rate'         => $this->faker->numberBetween(0,10),
            'is_more_enabled'   => false,
            'decimals'          => 4,
            'has_fixed_rate'    => false,
            'personal_fixed_rate' => 0,
            'corps_fixed_rate'  => 0,
            'imports_fixed_rate' => 0
        ];
    }
}
