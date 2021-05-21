<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Pair;
use App\Models\User;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Priority;
use App\Models\Recipient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class OrderTest extends TestCase
{
    use DatabaseMigrations, RefreshDatabase;

    /** @test */
    public function can_create_an_order()
    {
        $order = Order::create([
            'user_id'           => 1,
            'recipient_id'      => 1,
            'pair_id'           => 1,
            'priority_id'       => 1,
            'sended_amount'     => 1000,
            'received_amount'   => 2000,
            'rate'              => 2,
            'payment_amount'    => 1250,
            'transaction_cost'  => 100,
            'priority_cost'     => 100,
            'tax'               => 50,
            'tax_pct'           => 25,
            'total_cost'        => 250,
            'payment_code'      => 'ABCDEF123',
        ]);

        $this->assertInstanceOf(Order::class, $order);
        $this->assertDatabaseHas('orders', [
            'id'                => $order->id,
            'user_id'           => 1,
            'recipient_id'      => 1,
            'pair_id'           => 1,
            'priority_id'       => 1,
            'sended_amount'     => 1000,
            'received_amount'   => 2000,
            'rate'              => 2,
            'payment_amount'    => 1250,
            'transaction_cost'  => 100,
            'priority_cost'     => 100,
            'tax'               => 50,
            'tax_pct'           => 25,
            'total_cost'        => 250,
            'payment_code'      => 'ABCDEF123',
        ]);
    }

    /** @test */
    public function an_order_belongs_to_user()
    {
        $user = User::factory()->create();
        $order = Order::factory()->create([
            'user_id'   => $user->id,
        ]);

        $this->assertInstanceOf(User::class, $order->user);
        $this->assertEquals($user->id, $order->user->id);
    }

    /** @test */
    public function an_order_belongs_to_recipient()
    {
        $recipient = Recipient::factory()->create();
        $order = Order::factory()->create([
            'recipient_id' => $recipient->id,
        ]);

        $this->assertInstanceOf(Recipient::class, $order->recipient);
        $this->assertEquals($recipient->id, $order->recipient->id);
    }

    /** @test */
    public function an_order_belongs_to_a_pair()
    {
        $pair = Pair::factory()->create();
        $order = Order::factory()->create([
            'pair_id' => $pair->id,
        ]);

        $this->assertInstanceOf(Pair::class, $order->pair);
        $this->assertEquals($pair->id, $order->pair->id);
    }

    /** @test */
    public function an_order_belongs_to_a_priority()
    {
        $priority = Priority::factory()->create();
        $order = Order::factory()->create([
            'priority_id' => $priority->id,
        ]);

        $this->assertInstanceOf(Priority::class, $order->priority);
        $this->assertEquals($priority->id, $order->priority->id);
    }

    /** @test */
    public function if_an_order_is_filled()
    {
        $order = Order::factory()->create();
        $this->assertFalse($order->isFilled);

        $order = Order::factory()->create(['filled_at' => now()]);
        $this->assertTrue($order->isFilled);
    }

    /** @test */
    public function if_an_order_is_verified()
    {
        $order = Order::factory()->create();
        $this->assertFalse($order->isVerified);

        $order = Order::factory()->create(['verified_at' => now()]);
        $this->assertTrue($order->isVerified);
    }

    /** @test */
    public function if_an_order_is_rejected()
    {
        $order = Order::factory()->create();
        $this->assertFalse($order->isRejected);

        $order = Order::factory()->create(['rejected_at' => now()]);
        $this->assertTrue($order->isRejected);
    }

    /** @test */
    public function if_an_order_is_expired()
    {
        $order = Order::factory()->create();
        $this->assertFalse($order->isExpired);

        $order = Order::factory()->create(['expired_at' => now()]);
        $this->assertTrue($order->isExpired);
    }

    /** @test */
    public function if_an_order_is_completed()
    {
        $order = Order::factory()->create();
        $this->assertFalse($order->isCompleted);

        $order = Order::factory()->create(['completed_at' => now()]);
        $this->assertTrue($order->isCompleted);
    }

    /** @test */
    public function if_an_order_is_complianced()
    {
        $order = Order::factory()->create();
        $this->assertFalse($order->isComplianced);

        $order = Order::factory()->create(['complianced_at' => now()]);
        $this->assertTrue($order->isComplianced);
    }

    // public function order_status()
    // {
    //     // Order expired
    //     $order = Order::factory()->create(['expired_at' => now()]);
    //     $this->assertTrue($order->isExpired);
    //     $this->assertEquals($order->status, 'EXPIRED');

    //     // Order rejected
    //     $order = Order::factory()->create(['rejected_at' => now()]);
    //     $this->assertTrue($order->isRejected);
    //     $this->assertEquals($order->status, 'ORDER_REJECTED');

    //     // Payut Cancelled
    //     $order = Order::factory()->create(['rejected_at' => now()]);

    // }

    /** @test */
    public function an_order_has_incompleted_status()
    {
        $order = Order::factory()->create();

        $this->assertFalse($order->isCompleted);
        $this->assertFalse($order->isFilled);
        $this->assertFalse($order->isVerified);
        $this->assertFalse($order->isRejected);
        $this->assertFalse($order->isPayout);
        $this->assertFalse($order->isComplianced);
        $this->assertFalse($order->isPaymentVerified);
        $this->assertFalse($order->isPaymentRejected);
        $this->assertEquals($order->status, 'INCOMPLETED');
    }

    /** @test */
    public function an_order_has_filled_status()
    {
        $order = Order::factory()->create([
            'filled_at' => now()
        ]);

        $this->assertTrue($order->isFilled);
        $this->assertEquals($order->status, 'FILLED');
    }

    /** @test */
    public function an_order_has_expired_status()
    {
        $order = Order::factory()->create(['expired_at' => now()]);
        $this->assertTrue($order->isExpired);
        $this->assertEquals($order->status, 'EXPIRED');

        $order = Order::factory()->create([
            'filled_at' => now(),
            'expired_at' => now()
        ]);
        $this->assertTrue($order->isExpired);
        $this->assertEquals($order->status, 'EXPIRED');
    }

    /** @test */
    public function an_order_has_order_rejected_status()
    {
        $order = Order::factory()->create(['rejected_at' => now()]);
        $this->assertTrue($order->isRejected);
        $this->assertEquals($order->status, 'ORDER_REJECTED');

        $order = Order::factory()->create([
            'filled_at' => now(),
            'payed_at' => now(),
            'rejected_at' => now()
        ]);
        $this->assertTrue($order->isRejected);
        $this->assertEquals($order->status, 'ORDER_REJECTED');
    }

    /** @test */
    public function an_order_has_payment_rejected_status()
    {

        $order = Order::factory()->create([
            'payed_at' => now(),
            'rejected_at' => now()
        ]);

        Payment::factory()->create([
            'order_id' => $order->id,
            'rejected_at' => now()
        ]);

        $this->assertTrue($order->isPaymentRejected);
        $this->assertEquals($order->status, 'PAYMENT_REJECTED');
    }

    /** @test */
    public function an_order_has_order_verfied_status()
    {
        $order = Order::factory()->create(['verified_at' => now()]);

        $this->assertTrue($order->isVerified);
        $this->assertEquals($order->status, 'ORDER_VERIFIED');
    }

    /** @test */
    public function an_order_has_payment_verified_status()
    {
        $order = Order::factory()->create(['payed_at' => now()]);

        Payment::factory()->create([
            'order_id' => $order->id,
            'verified_at' => now()
        ]);

        $this->assertTrue($order->isPaymentVerified);
        $this->assertEquals($order->status, 'PAYMENT_VERIFIED');
    }

    /** @test */
    public function an_order_has_completed_status()
    {
        $order = Order::factory()->create([
            'completed_at' => now()
        ]);

        $this->assertTrue($order->isCompleted);
        $this->assertEquals($order->status, 'COMPLETED');
    }

    /** @test */
    public function an_order_has_payout_cancelled_status()
    {
        $order = Order::factory()->create([
            'filled_at' => now(),
            'payed_at' => now(),
            'payout_status' => 'Cancelled',
            'rejected_at' => now()
        ]);
        $this->assertTrue($order->isPayoutCancelled);
        $this->assertEquals($order->status, 'PAYOUT_CANCELLED');
    }

    /** @test */
    public function an_order_has_payout_received_status()
    {
        $order = Order::factory()->create([
            'filled_at' => now(),
            'payed_at' => now(),
            'payout_status' => 'Received',
        ]);
        $this->assertTrue($order->isPayoutReceived);
        $this->assertEquals($order->status, 'PAYOUT_RECEIVED');
    }

    /** @test */
    public function an_order_has_payout_completed_status()
    {
        $order = Order::factory()->create([
            'filled_at' => now(),
            'payed_at' => now(),
            'payout_status' => 'Completed',
        ]);
        $this->assertTrue($order->isPayoutCompleted);
        $this->assertEquals($order->status, 'PAYOUT_COMPLETED');
    }

    /** @test */
    public function an_order_has_payout_rejected_status()
    {
        $order = Order::factory()->create([
            'filled_at' => now(),
            'payed_at' => now(),
            'payout_status' => 'Rejected',
        ]);
        $this->assertTrue($order->isPayoutRejected);
        $this->assertEquals($order->status, 'PAYOUT_REJECTED');
    }


    /** @test */
    public function an_order_has_payout_on_hold_status()
    {
        $order = Order::factory()->create([
            'filled_at' => now(),
            'payed_at' => now(),
            'payout_status' => 'On Hold',
        ]);
        $this->assertTrue($order->isPayoutOnHold);
        $this->assertEquals($order->status, 'PAYOUT_ONHOLD');
    }

    /** @test */
    public function an_order_has_payout_delivered_status()
    {
        $order = Order::factory()->create([
            'filled_at' => now(),
            'payed_at' => now(),
            'payout_status' => 'Delivered',
        ]);
        $this->assertTrue($order->isPayoutDelivered);
        $this->assertEquals($order->status, 'PAYOUT_DELIVERED');
    }

    /** @test */
    public function a_order_has_a_payment()
    {
        $order = Order::factory()->create();
        $payment = Payment::factory()->create([
            'order_id' => $order->id
        ]);

        $this->assertInstanceOf(Order::class, $payment->order);
        $this->assertInstanceOf(Payment::class, $order->payment);
    }

    /** @test */
    public function can_validate_a_payment()
    {
        $order = Order::factory()->create([
            'filled_at'         => now(),
            'verified_at'       => null,
            'rejected_at'       => null,
            'expired_at'        => null,
            'completed_at'      => null,
            'complianced_at'    => null,
        ]);
        Payment::factory()->create([
            'order_id' => $order->id,
            'verified_at' => null,
            'rejected_at' => null
        ]);

        $this->assertTrue($order->canValidatePayment);

        $order = Order::factory()->create([
            'filled_at'         => now(),
            'verified_at'       => null,
            'rejected_at'       => null,
            'expired_at'        => null,
            'completed_at'      => null,
            'complianced_at'    => null,
        ]);
        Payment::factory()->create([
            'order_id' => $order->id,
            'verified_at' => now(),
            'rejected_at' => null
        ]);

        $this->assertFalse($order->canValidatePayment);

        $order = Order::factory()->create([
            'filled_at'         => now(),
            'verified_at'       => null,
            'rejected_at'       => null,
            'expired_at'        => null,
            'completed_at'      => null,
            'complianced_at'    => null,
        ]);
        Payment::factory()->create([
            'order_id' => $order->id,
            'verified_at' => null,
            'rejected_at' => now()
        ]);

        $this->assertFalse($order->canValidatePayment);

        $order = Order::factory()->create([
            'filled_at'         => now(),
            'verified_at'       => null,
            'rejected_at'       => now(),
            'expired_at'        => null,
            'completed_at'      => null,
            'complianced_at'    => null,
        ]);
        Payment::factory()->create([
            'order_id' => $order->id,
            'verified_at' => null,
            'rejected_at' => null
        ]);

        $this->assertFalse($order->canValidatePayment);

        $order = Order::factory()->create([
            'filled_at'         => now(),
            'verified_at'       => null,
            'rejected_at'       => null,
            'expired_at'        => now(),
            'completed_at'      => null,
            'complianced_at'    => null,
        ]);
        Payment::factory()->create([
            'order_id' => $order->id,
            'verified_at' => now(),
            'rejected_at' => null
        ]);

        $this->assertFalse($order->canValidatePayment);
    }

    /** @test */
    public function can_validate_order()
    {
        $order = Order::factory()->create([
            'filled_at'         => now(),
            'verified_at'       => null,
            'rejected_at'       => null,
            'expired_at'        => null,
            'completed_at'      => null,
            'complianced_at'    => null,
        ]);
        Payment::factory()->create([
            'order_id' => $order->id,
            'verified_at' => now(),
            'rejected_at' => null
        ]);
        $this->assertTrue($order->canValidateOrder);

        $order = Order::factory()->create([
            'filled_at'         => now(),
            'verified_at'       => null,
            'rejected_at'       => null,
            'expired_at'        => null,
            'completed_at'      => null,
            'complianced_at'    => null,
        ]);

        $this->assertFalse($order->canValidateOrder);

        $order = Order::factory()->create([
            'filled_at'         => now(),
            'verified_at'       => null,
            'rejected_at'       => now(),
            'expired_at'        => null,
            'completed_at'      => null,
            'complianced_at'    => null,
        ]);
        Payment::factory()->create([
            'order_id' => $order->id,
            'verified_at' => now(),
            'rejected_at' => null
        ]);

        $this->assertFalse($order->canValidateOrder);

        $order = Order::factory()->create([
            'filled_at'         => now(),
            'verified_at'       => null,
            'rejected_at'       => null,
            'expired_at'        => now(),
            'completed_at'      => null,
            'complianced_at'    => null,
        ]);
        Payment::factory()->create([
            'order_id' => $order->id,
            'verified_at' => now(),
            'rejected_at' => null
        ]);

        $this->assertFalse($order->canValidateOrder);

        $order = Order::factory()->create([
            'filled_at'         => now(),
            'verified_at'       => null,
            'rejected_at'       => null,
            'expired_at'        => null,
            'completed_at'      => now(),
            'complianced_at'    => null,
        ]);
        Payment::factory()->create([
            'order_id' => $order->id,
            'verified_at' => now(),
            'rejected_at' => null
        ]);
        $this->assertFalse($order->canValidateOrder);
    }

    /** @test */
    public function can_payout()
    {
        $order = Order::factory()->create([
            'filled_at'         => now(),
            'verified_at'       => now(),
            'rejected_at'       => null,
            'expired_at'        => null,
            'completed_at'      => null,
            'complianced_at'    => null,
        ]);
        Payment::factory()->create([
            'order_id' => $order->id,
            'verified_at' => now(),
            'rejected_at' => null
        ]);
        $this->assertTrue($order->canPayoutOrder);

        $order = Order::factory()->create([
            'filled_at'         => now(),
            'verified_at'       => null,
            'rejected_at'       => null,
            'expired_at'        => null,
            'completed_at'      => null,
            'complianced_at'    => null,
        ]);
        Payment::factory()->create([
            'order_id' => $order->id,
            'verified_at' => now(),
            'rejected_at' => null
        ]);
        $this->assertFalse($order->canPayoutOrder);

        $order = Order::factory()->create([
            'filled_at'         => now(),
            'verified_at'       => null,
            'rejected_at'       => now(),
            'expired_at'        => null,
            'completed_at'      => null,
            'complianced_at'    => null,
        ]);
        Payment::factory()->create([
            'order_id' => $order->id,
            'verified_at' => now(),
            'rejected_at' => null
        ]);
        $this->assertFalse($order->canPayoutOrder);

        $order = Order::factory()->create([
            'filled_at'         => now(),
            'verified_at'       => null,
            'rejected_at'       => null,
            'expired_at'        => now(),
            'completed_at'      => null,
            'complianced_at'    => null,
        ]);
        Payment::factory()->create([
            'order_id' => $order->id,
            'verified_at' => now(),
            'rejected_at' => null
        ]);
        $this->assertFalse($order->canPayoutOrder);

        $order = Order::factory()->create([
            'filled_at'         => now(),
            'verified_at'       => now(),
            'rejected_at'       => null,
            'expired_at'        => null,
            'completed_at'      => null,
            'complianced_at'    => null,
        ]);
        Payment::factory()->create([
            'order_id' => $order->id,
            'verified_at' => null,
            'rejected_at' => null
        ]);
        $this->assertFalse($order->canPayoutOrder);

        $order = Order::factory()->create([
            'filled_at'         => now(),
            'verified_at'       => null,
            'rejected_at'       => null,
            'expired_at'        => now(),
            'completed_at'      => null,
            'complianced_at'    => null,
        ]);
        Payment::factory()->create([
            'order_id' => $order->id,
            'verified_at' => null,
            'rejected_at' => now()
        ]);
        $this->assertFalse($order->canPayoutOrder);
    }
}
