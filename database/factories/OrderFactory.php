<?php

namespace Database\Factories;

use App\Models\Pair;
use App\Models\User;
use App\Models\Order;
use App\Models\Priority;
use App\Models\Recipient;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Order::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'user_id'           => User::factory()->create(),
            'recipient_id'      => Recipient::factory()->create(),
            'pair_id'           => Pair::factory()->create(),
            'priority_id'       => Priority::factory()->create(),
            'payment_amount'    => 100000,
            'sended_amount'     => 91670,
            'received_amount'   => 192860,
            'rate'              => 2,
            'transaction_cost'  => 2000,
            'priority_cost'     => 1000,
            'total_cost'        => 3000,
            'tax'               => 570,
            'tax_pct'           => 19,
            'payment_code'      => strtoupper(Str::random(12)),
            'filled_at'         => null,
            'verified_at'       => null,
            'rejected_at'       => null,
            'expired_at'        => null,
            'completed_at'      => null,
            'complianced_at'    => null,
            'purpose'           => null,
            'rejection_reason'  => null,
            'observation'       => $this->faker->sentence(),
            'purpose'           => $this->faker->word(),
        ];
    }
}
