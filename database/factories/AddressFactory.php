<?php

namespace Database\Factories;

use App\Models\Address;
use App\Models\Country;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AddressFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Address::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'user_id'           => User::factory()->create(),
            'country_id'        => Country::factory()->create(),
            'address'           => $this->faker->streetAddress(),
            'address_ext'       => $this->faker->secondaryAddress(),
            'state'             => $this->faker->state(),
            'city'              => $this->faker->city(),
            'cod'               => $this->faker->postcode(),
            'verified_at'       => null,
            'rejected_at'       => null
        ];
    }
}
