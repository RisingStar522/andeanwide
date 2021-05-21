<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Company;
use App\Models\Country;
use Illuminate\Database\Eloquent\Factories\Factory;

class CompanyFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Company::class;

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
            'name' => $this->faker->company(),
            'id_number' => $this->faker->isbn10(),
            'activity' => $this->faker->sentence(),
            'country_id' => $country->id,
            'has_politician_history' => true,
            'politician_history_charge' => $this->faker->jobTitle(),
            'politician_history_country_id' => $country->id,
            'politician_history_from' => '2015-01-01',
            'politician_history_to' => '2016-01-01',
            'activities' => $this->faker->sentence(),
            'anual_revenues' => 'GT_4MM_USD',
            'company_size' => 'large',
            'fund_origins' => "['uno', 'dos, 'three']",
            'verified_at' => null,
            'rejected_at' => null,
            'rejection_reasons' => null,
        ];
    }
}
