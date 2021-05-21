<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentType;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Payment::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'payment_type_id'       => PaymentType::factory()->create(),
            'order_id'              => Order::factory()->create(),
            'user_id'               => User::factory()->create(),
            'account_id'            => 1,
            'transaction_number'    => '123456789',
            'transaction_date'      => now(),
            'payment_amount'        => 100000,
            'payment_code'          => 'ABC',
            'observation'           => $this->faker->sentence(),
            'image_url'             => null,
            'verified_at'           => null,
            'rejected_at'           => null,
        ];
    }
}
