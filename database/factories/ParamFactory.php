<?php

namespace Database\Factories;

use App\Models\Param;
use Illuminate\Database\Eloquent\Factories\Factory;

class ParamFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Param::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name'          => $this->faker->word(),
            'label'         => $this->faker->word(),
            'description'   => $this->faker->sentence(),
            'value'         => $this->faker->word(),
            'value_type'    => 'string',
            'default_value' => null
        ];
    }
}
