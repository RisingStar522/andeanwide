<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Country;
use App\Models\Identity;
use Illuminate\Database\Eloquent\Factories\Factory;

class IdentityFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Identity::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'user_id'                   => User::factory()->create(),
            'identity_number'           => $this->faker->isbn10(),
            'document_type'             => 'dni',
            'firstname'                 => $this->faker->firstName(),
            'lastname'                  => $this->faker->lastName(),
            'dob'                       => now()->subYears(25),
            'issuance_date'             => now()->subYear(),
            'expiration_date'           => now()->addYear(),
            'gender'                    => 'M',
            'profession'                => $this->faker->word(),
            'activity'                  => $this->faker->word(),
            'position'                  => $this->faker->word(),
            'issuance_country_id'       => Country::factory()->create()->id,
            'nationality_country_id'    => Country::factory()->create()->id,
            'state'                     => 'NA',
            'verified_at'               => null,
            'rejected_at'               => null,
        ];
    }
}
