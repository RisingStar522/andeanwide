<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\AccountIncome;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AccountIncomeFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = AccountIncome::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'account_id' => Account::factory()->create(),
            'user_id' => User::factory()->create(),
            'transaction_id' => null,
            'origin' => '123456789',
            'transaction_number' => $this->faker->uuid(),
            'transaction_date' => now(),
            'amount' => 10000,
            'rejected_at' => null
        ];
    }
}
