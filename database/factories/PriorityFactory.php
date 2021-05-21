<?php

namespace Database\Factories;

use App\Models\Priority;
use Illuminate\Database\Eloquent\Factories\Factory;

class PriorityFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Priority::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name'          => $this->faker->sentence(3),
            'label'         => $this->faker->sentence(3),
            'sublabel'      => $this->faker->sentence(3),
            'description'   => $this->faker->paragraph(),
            'cost_pct'      => $this->faker->numberBetween(1,10),
            'is_active'     => true,
        ];
    }
}
