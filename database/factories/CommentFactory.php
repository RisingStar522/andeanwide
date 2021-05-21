<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Comment;
use Illuminate\Database\Eloquent\Factories\Factory;

class CommentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Comment::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'comments' => $this->faker->sentence(),
            'cashout_id' => null,
            'date' => now(),
            'bank_reference_id' => null,
            'commentable_id' => Order::factory()->create(),
            'commentable_type' => 'App\Models\Order'
        ];
    }
}
