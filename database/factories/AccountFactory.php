<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\Bank;
use App\Models\Country;
use App\Models\Currency;
use Illuminate\Database\Eloquent\Factories\Factory;

class AccountFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Account::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'is_active' => true,
            'country_id' => Country::factory()->create(),
            'currency_id' => Currency::factory()->create(),
            'bank_id' => Bank::factory()->create(),
            'label' => $this->faker->word,
            'bank_name' => $this->faker->company,
            'bank_account' => $this->faker->creditCardNumber,
            'account_name' => $this->faker->name,
            'account_type' => 'current',
            'document_number' => $this->faker->isbn10(),
            'description' => $this->faker->paragraph,
            'secret_key' => null
        ];
    }
}
