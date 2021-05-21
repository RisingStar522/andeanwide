<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Payment;
use App\Models\PaymentType;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class PaymentTypeTest extends TestCase
{
    use DatabaseMigrations, RefreshDatabase;

    /** @test */
    public function can_create_a_payment_type()
    {
        $paymentTest = PaymentType::create([
            'name'          => 'Name',
            'description'   => 'Description',
            'class_name'    => 'Class Name',
            'label'         => 'Label',
            'is_active'     => false
        ]);

        $this->assertInstanceOf(PaymentType::class, $paymentTest);
        $this->assertDatabaseHas('payment_types', [
            'name'          => 'Name',
            'description'   => 'Description',
            'label'         => 'Label',
            'class_name'    => 'Class Name',
            'is_active'     => false
        ]);
    }

    /** @test */
    public function payment_type_has_many_payments()
    {
        $paymentType = PaymentType::factory()->create();
        Payment::factory()->create(['payment_type_id' => $paymentType->id]);
        Payment::factory()->create(['payment_type_id' => $paymentType->id]);

        $this->assertInstanceOf(Collection::class, $paymentType->payments);
        $this->assertInstanceOf(Payment::class, $paymentType->payments[0]);
        $this->assertInstanceOf(Payment::class, $paymentType->payments[1]);
    }
}
