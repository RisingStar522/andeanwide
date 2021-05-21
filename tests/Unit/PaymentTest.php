<?php

namespace Tests\Unit;

use App\Models\Order;
use Tests\TestCase;
use App\Models\Payment;
use App\Models\PaymentType;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PaymentTest extends TestCase
{
    use DatabaseMigrations, RefreshDatabase;

    /** @test */
    public function can_create_a_payment()
    {
        $payment = Payment::create([
            'order_id' => 1,
            'user_id' => 1,
            'payment_type_id' => 1,
            'user_id' => 1,
            'account_id' => 1,
            'transaction_number' => 123,
            'transaction_date' => now(),
            'payment_amount' => 10000,
            'payment_code' => 'ABC123456789',
            'observation' => 'Observacion',
            'image_url' => 'http:://image.com/123',
            'verified_at' => now(),
            'rejected_at' => now()
        ]);

        $this->assertInstanceOf(Payment::class, $payment);
        $this->assertDatabaseHas('payments', [
            'order_id' => 1,
            'user_id' => 1,
            'payment_type_id' => 1,
            'user_id' => 1,
            'account_id' => 1,
            'transaction_number' => 123,
            'transaction_date' => now(),
            'payment_amount' => 10000,
            'payment_code' => 'ABC123456789',
            'observation' => 'Observacion',
            'image_url' => 'http:://image.com/123',
            'verified_at' => now(),
            'rejected_at' => now()
        ]);
    }

    /** @test */
    public function a_payment_belongs_to_a_payment_type()
    {
        $paymentType = PaymentType::factory()->create();
        $payment = Payment::factory()->create(['payment_type_id' => $paymentType->id]);

        $this->assertInstanceOf(PaymentType::class, $payment->paymentType);
    }

    /** @test */
    public function a_payment_belongs_to_an_order()
    {
        $order = Order::factory()->create();
        $payment = Payment::factory()->create(['order_id' => $order->id]);

        $this->assertInstanceOf(Order::class, $payment->order);
    }

    /** @test */
    public function a_payment_belongs_to_an_user()
    {
        $user = User::factory()->create();
        $payment = Payment::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $payment->user);
    }
}
