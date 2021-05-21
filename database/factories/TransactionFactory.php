<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Account;
use App\Models\Currency;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;

class TransactionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Transaction::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'user_id' => User::factory()->create(),
            'account_id' => Account::factory()->create(),
            'order_id' => null,
            'external_id' => $this->faker->uuid(),
            'type' => 'income',
            'amount' => 10000,
            'amount_usd' => 14,
            'currency_id' => Currency::factory()->create(),
            'note' => $this->faker->sentence(),
            'rejected_at' => null,
            'transaction_date' => now()
        ];
    }
}
