<?php

namespace Tests\Feature;

use App\Models\Order;
use Tests\TestCase;
use App\Models\User;
use App\Models\Payment;
use Laravel\Sanctum\Sanctum;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class PaymentTest extends TestCase
{
    use RefreshDatabase, DatabaseMigrations, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    /** @test */
    public function non_authenticated_user_cannot_view_index_of_payments()
    {
        $this->json('get', 'api/payments')
            ->assertUnauthorized();
    }

    /** @test */
    public function non_user_with_user_role_cannot_view_index_of_payments()
    {
        $user = User::factory()->create();
        $user->assignRole(['base', 'admin', 'super_admin', 'compliance', 'agent']);
        Sanctum::actingAs($user);

        $this->json('get', 'api/payments')
            ->assertForbidden();
    }

    /** @test */
    public function user_with_role_user_can_view_only_his_own_payments()
    {
        $user = User::factory()->create();
        $user->assignRole(['user']);
        Sanctum::actingAs($user);

        Payment::factory()->count(10)->create([
            'user_id'   => $user->id
        ]);
        Payment::factory()->count(5)->create();

        $this->json('get', 'api/payments')
            ->assertOk()
            ->assertJsonCount(10, 'data')
            ->assertJsonStructure([
                'data' => [
                    [
                        'id',
                        'order_id',
                        'user_id',
                        'account_id',
                        'transaction_number',
                        'transaction_date',
                        'payment_amount',
                        'payment_code',
                        'observation',
                        'image_url',
                        'verified_at',
                        'rejected_at',
                        'created_at',
                        'updated_at',
                    ]
                ]
            ]);
    }

    /** @test */
    public function non_authenticated_user_cannot_create_a_payment()
    {
        $this->json('post', 'api/payments')
        ->assertUnauthorized();
    }

    /** @test */
    public function non_user_with_user_role_cannot_create_a_payment()
    {
        $user = User::factory()->create();
        $user->assignRole(['base', 'admin', 'super_admin', 'compliance', 'agent']);
        Sanctum::actingAs($user);

        $this->json('post', 'api/payments')
            ->assertForbidden();
    }

    /** @test */
    public function an_user_with_user_role_can_create_a_payment()
    {
        $user = User::factory()->create();
        $user->assignRole(['user']);
        Sanctum::actingAs($user);

        $payment = Payment::factory()->raw([
            'user_id' => null
        ]);

        $this->json('post', 'api/payments', $payment)
            ->assertCreated();

        $this->assertDatabaseHas('payments', [
            'order_id'  => $payment['order_id'],
            'user_id'  => $user->id,
            'account_id'  => $payment['account_id'],
            'transaction_number'  => $payment['transaction_number'],
            'payment_amount'  => $payment['payment_amount'],
            'payment_code'  => $payment['payment_code'],
            'observation'  => $payment['observation'],
        ]);

        $this->assertNotNull(Order::find($payment['order_id'])->filled_at);
    }

    /** @test */
    public function an_user_cannot_create_a_payment_without_required_fields()
    {
        $user = User::factory()->create();
        $user->assignRole(['user']);
        Sanctum::actingAs($user);

        $payment = Payment::factory()->raw([
            'user_id' => null,
            'order_id' => null,
            'payment_type_id' => null,
            'transaction_number' => null,
            'transaction_date' => null,
            'payment_amount' => null
        ]);

        $this->json('post', 'api/payments', $payment)
            ->assertStatus(422)
            ->assertJsonFragment(['The order id field is required.'])
            ->assertJsonFragment(['The payment type id field is required.'])
            ->assertJsonFragment(['The transaction number field is required.'])
            ->assertJsonFragment(['The transaction date field is required.'])
            ->assertJsonFragment(['The payment amount field is required.']);
    }

    /** @test */
    public function to_create_a_payment_the_order_and_payment_type_must_exist()
    {
        $user = User::factory()->create();
        $user->assignRole(['user']);
        Sanctum::actingAs($user);

        $payment = Payment::factory()->raw([
            'user_id' => null,
            'order_id' => 200,
            'payment_type_id' => 100,
        ]);

        $this->json('post', 'api/payments', $payment)
            ->assertStatus(422)
            ->assertJsonFragment(['The selected order id is invalid.'])
            ->assertJsonFragment(['The selected payment type id is invalid.']);
    }

    /** @test */
    public function to_create_a_payment_must_be_mnumeric()
    {
        $user = User::factory()->create();
        $user->assignRole(['user']);
        Sanctum::actingAs($user);

        $payment = Payment::factory()->raw([
            'user_id' => null,
            'payment_amount' => 'abc',
        ]);

        $this->json('post', 'api/payments', $payment)
            ->assertStatus(422)
            ->assertJsonFragment(['The payment amount must be a number.']);
    }

    /** @test */
    public function non_authenticated_user_cannot_view_single_payment()
    {
        $this->json('get', 'api/payments/100')
            ->assertUnauthorized();
    }

    /** @test */
    public function cannot_view_a_non_existing_payment()
    {
        $user = User::factory()->create();
        $user->assignRole(['base', 'admin', 'super_admin', 'compliance', 'agent']);
        Sanctum::actingAs($user);

        $this->json('get', 'api/payments/100')
            ->assertNotFound();
    }

    /** @test */
    public function user_with_no_user_cannot_view_a_single_payment()
    {
        $user = User::factory()->create();
        $user->assignRole(['base', 'admin', 'super_admin', 'compliance', 'agent']);
        Sanctum::actingAs($user);

        $payment = Payment::factory()->create([
            'user_id' => $user->id,
        ]);

        $this->json('get', 'api/payments/' . $payment->id)
            ->assertForbidden();
    }

    /** @test */
    public function an_user_can_view_a_single_payment()
    {
        $user = User::factory()->create();
        $user->assignRole(['user']);
        Sanctum::actingAs($user);

        $payment = Payment::factory()->create([
            'user_id' => $user->id,
        ]);

        $this->json('get', 'api/payments/' . $payment->id)
            ->assertOk()
            ->assertJsonFragment(['id' => $payment->id])
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'order_id',
                    'user_id',
                    'account_id',
                    'transaction_number',
                    'transaction_date',
                    'payment_amount',
                    'payment_code',
                    'observation',
                    'image_url',
                    'verified_at',
                    'rejected_at',
                    'created_at',
                    'updated_at',
                ]
            ]);
    }

    /** @test */
    public function an_user_cannot_view_a_single_payment_if_its_not_belongs_to_him()
    {
        $user = User::factory()->create();
        $user->assignRole(['user']);
        Sanctum::actingAs($user);

        $payment = Payment::factory()->create();

        $this->json('get', 'api/payments/' . $payment->id)
            ->assertNotFound();
    }
}
