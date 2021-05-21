<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Order;
use App\Models\Payment;
use App\Actions\Helpers\PaymentAction;
use Database\Seeders\PaymentTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class PaymentActionTest extends TestCase
{
    use DatabaseMigrations, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PaymentTypeSeeder::class);
    }

    /** @test */
    public function payment_action_can_create_a_payment_to_an_order()
    {
        $user = User::factory()->create([
            'balance' => 500000
        ]);
        $order = Order::factory()->create([
            'filled_at' => null,
            'user_id' => $user->id,
            'payment_amount' => 350000
        ]);
        $payment = PaymentAction::createPayment($order);
        $order->refresh();
        $user->refresh();
        $this->assertNotNull($payment);
        $this->assertInstanceOf(Payment::class, $payment);
        $this->assertEquals($user->balance, 150000);
    }

    /** @test */
    public function cannot_create_payment_if_filled_is_not_null()
    {
        $user = User::factory()->create([
            'balance' => 500000
        ]);
        $order = Order::factory()->create([
            'filled_at' => now(),
            'user_id' => $user->id,
            'payment_amount' => 350000
        ]);
        $payment = PaymentAction::createPayment($order);
        $order->refresh();
        $user->refresh();
        $this->assertNull($payment);
        $this->assertEquals($user->balance, 500000);
    }

    /** @test */
    public function can_reject_payment()
    {
        $user = User::factory()->create(['balance' => 0]);
        $order = Order::factory()->create([
            'filled_at' => now(),
            'user_id' => $user->id,
            'payment_amount' => 300000
        ]);
        Payment::factory()->create(['order_id' => $order->id, 'payment_amount' => 300000]);

        $payment = PaymentAction::rejectPayment($order, 'lorem ipsum');
        $order->refresh();
        $user->refresh();
        $this->assertNotNull($payment);
        $this->assertNotNull($payment->rejected_at);
        $this->assertInstanceOf(Payment::class, $payment);
        $this->assertEquals($user->balance, 300000);
    }

    /** @test */
    public function cannot_reject_an_order_rejected()
    {
        $user = User::factory()->create(['balance' => 0]);
        $order = Order::factory()->create([
            'filled_at' => now(),
            'rejected_at' => now(),
            'user_id' => $user->id,
            'payment_amount' => 300000
        ]);
        Payment::factory()->create(['order_id' => $order->id, 'payment_amount' => 300000]);

        $payment = PaymentAction::rejectPayment($order, 'lorem ipsum');
        $order->refresh();
        $user->refresh();
        $this->assertNull($payment);
        $this->assertEquals($user->balance, 0);
    }

    /** @test */
    public function cannot_reject_an_expired_order()
    {
        $user = User::factory()->create(['balance' => 0]);
        $order = Order::factory()->create([
            'filled_at' => now(),
            'expired_at' => now(),
            'user_id' => $user->id,
            'payment_amount' => 300000
        ]);
        Payment::factory()->create(['order_id' => $order->id, 'payment_amount' => 300000]);

        $payment = PaymentAction::rejectPayment($order, 'lorem ipsum');
        $order->refresh();
        $user->refresh();
        $this->assertNull($payment);
        $this->assertEquals($user->balance, 0);
    }
}
