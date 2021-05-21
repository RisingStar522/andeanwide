<?php

namespace Database\Factories;

use App\Models\Country;
use App\Models\User;
use App\Models\Remitter;
use Illuminate\Database\Eloquent\Factories\Factory;

class RemitterFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Remitter::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $country = Country::factory()->create();

        return [
            'user_id' => User::factory()->create(),
            'fullname' => $this->faker->name(),
            'document_type' => 'PASS',
            'dni' => $this->faker->isbn10(),
            'issuance_date' => '2020-01-01',
            'expiration_date' => '2022-01-01',
            'dob' => '2000-01-01',
            'address' => $this->faker->address(),
            'city' => $this->faker->city(),
            'state' => $this->faker->state(),
            'country_id' => $country->id,
            'issuance_country_id' => $country->id,
            'phone' => $this->faker->phoneNumber(),
            'email' => $this->faker->email(),
            'document_url' => $this->faker->imageUrl(),
            'reverse_url' => $this->faker->imageUrl(),
        ];
    }
}
