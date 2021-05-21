<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Pair;
use App\Models\User;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Currency;
use App\Models\Priority;
use App\Models\Remitter;
use App\Models\Recipient;
use App\Jobs\OrderExpiracy;
use App\Models\PaymentType;
use Laravel\Sanctum\Sanctum;
use Database\Seeders\RoleSeeder;
use Database\Seeders\ParamSeeder;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Database\Seeders\PaymentTypeSeeder;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class OrderTest extends TestCase
{
    use DatabaseMigrations, RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->seed(ParamSeeder::class);
        $this->seed(PaymentTypeSeeder::class);
    }

    /** @test */
    public function non_authenticated_view_index_of_orders_for_users()
    {
        $this->json('get', 'api/orders')
            ->assertUnauthorized();
    }

    /** @test */
    public function admin_users_cannot_view_index_of_orders_for_users()
    {
        $user = User::factory()->create();
        $user->assignRole(['base', 'super_admin' ,'admin', 'compliance', 'agent']);
        Sanctum::actingAs($user);

        $this->json('get', 'api/orders')
            ->assertForbidden();
    }

    /** @test */
    public function user_can_view_a_index_of_orders_for_users()
    {
        $user = User::factory()->create();
        $user->assignRole(['user']);
        Sanctum::actingAs($user);

        Order::factory()->count(10)->create([
            'user_id' => $user->id
        ]);
        Order::factory()->count(5)->create();

        $this->json('get', 'api/orders')
            ->assertOk()
            ->assertJsonCount(10, 'data')
            ->assertJsonStructure([
                'data' => [
                    [
                        'id',
                        'user_id',
                        'recipient' => [],
                        'pair' => [],
                        'priority' => [],
                        'payment' => [],
                        'sended_amount',
                        'received_amount',
                        'rate',
                        'payment_amount',
                        'transaction_cost',
                        'priority_cost',
                        'tax',
                        'tax_pct',
                        'total_cost',
                        'payment_code',
                        'filled_at',
                        'verified_at',
                        'rejected_at',
                        'expired_at',
                        'completed_at',
                        'complianced_at',
                        'status',
                        'rejection_reason',
                        'observation',
                        'created_at',
                        'updated_at',
                        'remitter'
                    ],
                ],
                'meta',
                'links',
            ]);
    }

    /** @test */
    public function non_authenticated_user_cannot_view_single_order_for_user()
    {
        $this->json('get', 'api/orders/100')
            ->assertUnauthorized();
    }

    /** @test */
    public function cannot_view_non_existing_order()
    {
        $user = User::factory()->create();
        $user->assignRole(['base', 'super_admin' ,'admin', 'super_admin', 'compliance', 'agent']);
        Sanctum::actingAs($user);

        $this->json('get', 'api/orders/100')
            ->assertNotFound();
    }

    /** @test */
    public function an_administrative_user_cannot_view_a_single_order_for_user()
    {
        $user = User::factory()->create();
        $user->assignRole(['base', 'super_admin' ,'admin', 'super_admin', 'compliance', 'agent']);
        Sanctum::actingAs($user);

        $order = Order::factory()->create();

        $this->json('get', 'api/orders/' . $order->id)
            ->assertForbidden();
    }

    /** @test */
    public function an_user_can_view_a_single_order_of_its_own()
    {
        $user = User::factory()->create();
        $user->assignRole(['user']);
        Sanctum::actingAs($user);

        $order = Order::factory()->create([
            'user_id' => $user->id
        ]);

        $this->json('get', 'api/orders/' . $order->id)
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                'id',
                'user_id',
                'recipient' => [],
                'pair' => [],
                'priority' => [],
                'payment' => [],
                'sended_amount',
                'received_amount',
                'rate',
                'payment_amount',
                'transaction_cost',
                'priority_cost',
                'tax',
                'tax_pct',
                'total_cost',
                'payment_code',
                'filled_at',
                'verified_at',
                'rejected_at',
                'expired_at',
                'completed_at',
                'complianced_at',
                'status',
                'rejection_reason',
                'observation',
                'purpose',
                'created_at',
                'updated_at',
            ],
        ]);
    }

    /** @test */
    public function an_user_cannot_view_an_order_that_does_not_belongs_to_him()
    {
        $user = User::factory()->create();
        $user->assignRole(['user']);
        Sanctum::actingAs($user);

        $order = Order::factory()->create();

        $this->json('get', 'api/orders/' . $order->id)
            ->assertForbidden();
    }

    /** @test */
    public function non_authenticated_user_cannot_create_an_order()
    {
        $this->json('post', 'api/orders')
            ->assertUnauthorized();
    }

    /** @test */
    public function an_administrative_user_cannot_create_an_order()
    {
        $user = User::factory()->create();
        $user->assignRole(['base', 'super_admin' ,'admin', 'compliance', 'agent']);
        Sanctum::actingAs($user);

        $this->json('post', 'api/orders')
            ->assertForbidden();
    }

    /** @test */
    public function an_user_can_create_an_order()
    {
        Http::fake([
            'apilayer.net/*' => Http::response([
                'success' => true,
                'timestamp' => 1,
                'source' => 'USD',
                'quotes' => [
                    'USDCOP' => 0.0004,
                ]
            ], 200, ['Headers'])
        ]);

        Bus::fake();
        $user = User::factory()->create();
        $user->assignRole(['user']);
        Sanctum::actingAs($user);

        $priority = Priority::factory()->create([
            'name'      => 'normal',
            'cost_pct'  => 5,
        ]);

        $recipient = Recipient::factory()->create();
        $quote_currency = Currency::factory()->create([
            'name' => 'COP',
            'symbol' => 'COP'
        ]);
        $pair = Pair::factory()->create([
            'quote_id' => $quote_currency->id,
            'has_fixed_rate' => true,
            'personal_fixed_rate' => 2
        ]);
        $order = Order::factory()->raw([
            'recipient_id'      => $recipient->id,
            'payment_amount'    => 500000,
            'rate'              => 2,
            'priority_id'       => $priority->id,
            'pair_id'           => $pair->id
        ]);

        $this->json('post', 'api/orders', $order)
            ->assertCreated();

        Bus::assertDispatched(OrderExpiracy::class);

        $this->assertDatabaseHas('orders', [
            'user_id'           => $user->id,
            'recipient_id'      => $order['recipient_id'],
            'pair_id'           => $order['pair_id'],
            'priority_id'       => $order['priority_id'],
            'payment_amount'    => 500000,
            'sended_amount'     => 458350,
            'received_amount'   => 916700,
            'usd_amount'        => 366.68,
            'transaction_cost'  => 10000,
            'priority_cost'     => 25000,
            'total_cost'        => 35000,
            'tax'               => 6650,
            'tax_pct'           => 19,
            'filled_at'         => null,
            'verified_at'       => null,
            'rejected_at'       => null,
            'expired_at'        => null,
            'completed_at'      => null,
            'complianced_at'    => null,
            'purpose'           => $order['purpose']
        ]);

        $this->assertDatabaseMissing('payments', [
            'user_id' => $user->id,
            'payment_amount' => $order['payment_amount']
        ]);

        $user->refresh();
        $this->assertEquals(0, $user->balance);
    }

    /** @test */
    public function if_user_has_balance_discount_it_and_create_a_transaction()
    {
        Bus::fake();
        $user = User::factory()->create(['balance' => 800000, 'balance_credit_limit' => 0]);
        $user->assignRole(['user']);
        Sanctum::actingAs($user);

        $priority = Priority::factory()->create([
            'name'      => 'normal',
            'cost_pct'  => 5,
        ]);
        $recipient = Recipient::factory()->create();
        $pair = Pair::factory()->create([
            'has_fixed_rate' => true,
            'personal_fixed_rate' => 2
        ]);
        $order = Order::factory()->raw([
            'recipient_id'      => $recipient->id,
            'payment_amount'    => 500000,
            'rate'              => 2,
            'priority_id'       => $priority->id,
            'pair_id'           => $pair->id
        ]);

        $this->json('post', 'api/orders', $order)
            ->assertCreated();

        Bus::assertDispatched(OrderExpiracy::class);

        $user->refresh();
        $this->assertEquals($user->balance, 300000);

        $order = Order::where([
            'recipient_id'      => $recipient->id,
            'payment_amount'    => 500000,
            'rate'              => 2,
            'priority_id'       => $priority->id,
            'pair_id'           => $pair->id
        ])->first();
        $this->assertNotNull($order->filled_at);
        $this->assertNotNull($order->payment);
        $this->assertNotNull($order->payment->verified_at);

        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'user_id' => $user->id,
            'payment_amount' => $order->payment_amount
        ]);

        $this->assertDatabaseHas('transactions', [
            'order_id' => $order->id,
            'user_id' => $user->id,
            'type' => 'outcome',
            'amount' => $order->payment_amount
        ]);
    }

    /** @test */
    public function create_an_order_if_the_user_has_no_balance_but_has_credit_enough_proceed_to_pay_with_balance()
    {
        Bus::fake();
        $user = User::factory()->create(['balance' => 10000, 'balance_credit_limit' => 20000]);
        $user->assignRole(['user']);
        Sanctum::actingAs($user);

        $priority = Priority::factory()->create([
            'name'      => 'normal',
            'cost_pct'  => 5,
        ]);
        $recipient = Recipient::factory()->create();
        $pair = Pair::factory()->create([
            'has_fixed_rate' => true,
            'personal_fixed_rate' => 2
        ]);
        $order = Order::factory()->raw([
            'recipient_id'      => $recipient->id,
            'payment_amount'    => 25000,
            'rate'              => 2,
            'priority_id'       => $priority->id,
            'pair_id'           => $pair->id
        ]);

        $this->json('post', 'api/orders', $order)
            ->assertCreated();

        Bus::assertDispatched(OrderExpiracy::class);

        $user->refresh();
        $this->assertEquals($user->balance, -15000);

        $order = Order::where([
            'recipient_id'      => $recipient->id,
            'payment_amount'    => 25000,
            'rate'              => 2,
            'priority_id'       => $priority->id,
            'pair_id'           => $pair->id
        ])->first();
        $this->assertNotNull($order->filled_at);
        $this->assertNotNull($order->payment);
        $this->assertNotNull($order->payment->verified_at);

        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'user_id' => $user->id,
            'payment_amount' => $order->payment_amount
        ]);

        $this->assertDatabaseHas('transactions', [
            'order_id' => $order->id,
            'user_id' => $user->id,
            'type' => 'outcome',
            'amount' => $order->payment_amount
        ]);
    }

        /** @test */
        public function create_an_order_and_if_the_payment_amount_is_greater_than_the_balance_and_credit_cannot_create_a_payment()
        {
            Bus::fake();
            $user = User::factory()->create(['balance' => 10000, 'balance_credit_limit' => 20000]);
            $user->assignRole(['user']);
            Sanctum::actingAs($user);

            $priority = Priority::factory()->create([
                'name'      => 'normal',
                'cost_pct'  => 5,
            ]);
            $recipient = Recipient::factory()->create();
            $pair = Pair::factory()->create([
                'has_fixed_rate' => true,
                'personal_fixed_rate' => 2
            ]);
            $order = Order::factory()->raw([
                'recipient_id'      => $recipient->id,
                'payment_amount'    => 50000,
                'rate'              => 2,
                'priority_id'       => $priority->id,
                'pair_id'           => $pair->id
            ]);

            $this->json('post', 'api/orders', $order)
                ->assertCreated();

            Bus::assertDispatched(OrderExpiracy::class);

            $user->refresh();
            $this->assertEquals($user->balance, 10000);
            $this->assertEquals($user->balance_credit_limit, 20000);

            $order = Order::where([
                'recipient_id'      => $recipient->id,
                'payment_amount'    => 50000,
                'rate'              => 2,
                'priority_id'       => $priority->id,
                'pair_id'           => $pair->id
            ])->first();
            $this->assertNull($order->filled_at);
            $this->assertNull($order->payment);

            $this->assertDatabaseMissing('payments', [
                'order_id' => $order->id,
                'user_id' => $user->id,
                'payment_amount' => $order->payment_amount
            ]);
        }

    /** @test */
    public function an_agent_can_create_order_with_remitter()
    {
        Bus::fake();
        $user = User::factory()->create();
        $user->assignRole(['user', 'agent']);
        Sanctum::actingAs($user);

        $priority = Priority::factory()->create([
            'name'      => 'normal',
            'cost_pct'  => 5,
        ]);

        $recipient = Recipient::factory()->create();
        $remitter = Remitter::factory()->create(['user_id' => $user->id]);

        $pair = Pair::factory()->create([
            'has_fixed_rate' => true,
            'personal_fixed_rate' => 2
        ]);

        $order = Order::factory()->raw([
            'recipient_id'      => $recipient->id,
            'payment_amount'    => 500000,
            'rate'              => 2,
            'priority_id'       => $priority->id,
            'remitter_id'       => $remitter->id,
            'pair_id'           => $pair->id
        ]);

        $this->json('post', 'api/orders', $order)
            ->assertCreated();

        Bus::assertDispatched(OrderExpiracy::class);

        $this->assertDatabaseHas('orders', [
                'user_id'           => $user->id,
                'recipient_id'      => $order['recipient_id'],
                'pair_id'           => $order['pair_id'],
                'priority_id'       => $order['priority_id'],
                'payment_amount'    => 500000,
                'sended_amount'     => 458350,
                'received_amount'   => 916700,
                'transaction_cost'  => 10000,
                'priority_cost'     => 25000,
                'total_cost'        => 35000,
                'tax'               => 6650,
                'tax_pct'           => 19,
                'filled_at'         => null,
                'verified_at'       => null,
                'rejected_at'       => null,
                'expired_at'        => null,
                'completed_at'      => null,
                'complianced_at'    => null,
                'remitter_id'       => $remitter->id
            ]);
    }

    /** @test */
    public function can_not_create_an_order_if_the_rate_chenge()
    {
        $user = User::factory()->create();
        $user->assignRole(['user']);
        Sanctum::actingAs($user);

        $priority = Priority::factory()->create([
            'name'      => 'normal',
            'cost_pct'  => 5,
        ]);

        $recipient = Recipient::factory()->create();
        $pair = Pair::factory()->create([
            'has_fixed_rate' => true,
            'personal_fixed_rate' => 5
        ]);
        $order = Order::factory()->raw([
            'recipient_id'      => $recipient->id,
            'payment_amount'    => 500000,
            'rate'              => 2,
            'priority_id'       => $priority->id,
            'pair_id'           => $pair->id
        ]);

        $this->json('post', 'api/orders', $order)
            ->assertStatus(422)
            ->assertJsonFragment(['The rate field has changed, is not possible to create a new order.']);
    }

    /** @test */
    public function cannot_create_an_order_without_required_fields()
    {
        $user = User::factory()->create();
        $user->assignRole(['user']);
        Sanctum::actingAs($user);

        $order = Order::factory()->raw([
            'recipient_id'      => null,
            'payment_amount'    => null,
            'rate'              => null,
            'priority_id'       => null,
            'pair_id'           => null
        ]);

        $this->json('post', 'api/orders', $order)
            ->assertStatus(422)
            ->assertJsonFragment(['The recipient id field is required.'])
            ->assertJsonFragment(['The pair id field is required.'])
            ->assertJsonFragment(['The priority id field is required.'])
            ->assertJsonFragment(['The payment amount field is required.'])
            ->assertJsonFragment(['The rate field is required.']);
    }

    /** @test */
    public function cannot_create_an_order_if_recipient_id_does_not_exist()
    {
        $user = User::factory()->create();
        $user->assignRole(['user']);
        Sanctum::actingAs($user);

        $order = Order::factory()->raw([
            'recipient_id'  => 100,
        ]);

        $this->json('post', 'api/orders', $order)
            ->assertStatus(422)
            ->assertJsonFragment(['The selected recipient id is invalid.']);
    }

    /** @test */
    public function cannot_create_an_order_if_pair_id_does_not_exists()
    {
        $user = User::factory()->create();
        $user->assignRole(['user']);
        Sanctum::actingAs($user);

        $order = Order::factory()->raw([
            'pair_id'  => 100,
        ]);

        $this->json('post', 'api/orders', $order)
            ->assertStatus(422)
            ->assertJsonFragment(['The selected pair id is invalid.']);
    }

    /** @test */
    public function cannot_create_an_order_if_priority_id_does_not_exists()
    {
        $user = User::factory()->create();
        $user->assignRole(['user']);
        Sanctum::actingAs($user);

        $order = Order::factory()->raw([
            'priority_id'  => 100,
        ]);

        $this->json('post', 'api/orders', $order)
            ->assertStatus(422)
            ->assertJsonFragment(['The selected priority id is invalid.']);
    }

    /** @test */
    public function sended_amount_must_be_a_number()
    {
        $user = User::factory()->create();
        $user->assignRole(['user']);
        Sanctum::actingAs($user);

        $order = Order::factory()->raw([
            'payment_amount'  => 'string',
        ]);

        $this->json('post', 'api/orders', $order)
            ->assertStatus(422)
            ->assertJsonFragment(['The payment amount must be a number.']);
    }

    /** @test */
    public function non_authenticated_user_cannot_view_index_of_order_for_admin()
    {
        $this->json('get', '/api/admin/orders')
            ->assertUnauthorized();
    }

    /** @test */
    public function non_admin_nor_compliance_cannot_view_index_of_orders_for_admin()
    {
        $user = User::factory()->create();
        $user->assignRole(['user', 'base', 'super_admin', 'agent']);
        Sanctum::actingAs($user);

        $this->json('get', '/api/admin/orders')
            ->assertForbidden();
    }

    /** @test */
    public function admin_user_can_view_index_of_orders_for_admin()
    {
        $user = User::factory()->create();
        $user->assignRole(['base', 'admin']);
        Sanctum::actingAs($user);

        Order::factory()->count(10)->create([
            'filled_at' => now()
        ]);
        Order::factory()->count(5)->create([
            'filled_at' => null
        ]);

        $this->json('get', '/api/admin/orders')
            ->assertOk()
            ->assertJsonCount(10, 'data')
            ->assertJsonStructure([
                'data' => [
                    [
                        'id',
                        'user_id',
                        'recipient' => [],
                        'pair' => [],
                        'priority' => [],
                        'payment' => [],
                        'sended_amount',
                        'received_amount',
                        'rate',
                        'payment_amount',
                        'transaction_cost',
                        'priority_cost',
                        'tax',
                        'tax_pct',
                        'total_cost',
                        'payment_code',
                        'filled_at',
                        'verified_at',
                        'rejected_at',
                        'expired_at',
                        'completed_at',
                        'complianced_at',
                        'status',
                        'rejection_reason',
                        'observation',
                        'created_at',
                        'updated_at',
                    ],
                ],
                'meta',
                'links',
            ]);
    }

    /** @test */
    public function a_compliance_user_can_view_index_of_orders_for_admin()
    {
        $user = User::factory()->create();
        $user->assignRole(['base', 'compliance']);
        Sanctum::actingAs($user);

        Order::factory()->count(10)->create([
            'filled_at' => now()
        ]);
        Order::factory()->count(5)->create([
            'filled_at' => null
        ]);

        $this->json('get', '/api/admin/orders')
            ->assertOk()
            ->assertJsonCount(10, 'data')
            ->assertJsonStructure([
                'data' => [
                    [
                        'id',
                        'user_id',
                        'recipient' => [],
                        'pair' => [],
                        'priority' => [],
                        'payment' => [],
                        'sended_amount',
                        'received_amount',
                        'rate',
                        'payment_amount',
                        'transaction_cost',
                        'priority_cost',
                        'tax',
                        'tax_pct',
                        'total_cost',
                        'payment_code',
                        'filled_at',
                        'verified_at',
                        'rejected_at',
                        'expired_at',
                        'completed_at',
                        'complianced_at',
                        'status',
                        'rejection_reason',
                        'observation',
                        'created_at',
                        'updated_at',
                    ],
                ],
                'meta',
                'links',
            ]);
    }

    /** @test */
    public function non_authenticated_user_cannot_vieww_a_single_order_for_admin()
    {
        $this->json('get', '/api/admin/orders/100')
            ->assertUnauthorized();
    }

    /** @test */
    public function cannot_view_non_existing_order_in_single_order_for_admin()
    {
        $user = User::factory()->create();
        $user->assignRole(['user', 'base', 'super_admin', 'agent']);
        Sanctum::actingAs($user);

        $this->json('get', '/api/admin/orders/100')
            ->assertNotFound();
    }

    /** @test */
    public function non_admin_nor_compliance_user_cannot_view_single_order_for_admin()
    {
        $user = User::factory()->create();
        $user->assignRole(['user', 'base', 'super_admin', 'agent']);
        Sanctum::actingAs($user);

        $order = Order::factory()->create();

        $this->json('get', '/api/admin/orders/' . $order->id)
            ->assertForbidden();
    }

    /** @test */
    public function admin_user_can_view_a_single_order_for_admin()
    {
        $user = User::factory()->create();
        $user->assignRole(['base', 'admin']);
        Sanctum::actingAs($user);

        $order = Order::factory()->create();

        $this->json('get', '/api/admin/orders/' . $order->id)
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'user_id',
                    'recipient' => [],
                    'pair' => [],
                    'priority' => [],
                    'payment' => [],
                    'sended_amount',
                    'received_amount',
                    'rate',
                    'payment_amount',
                    'transaction_cost',
                    'priority_cost',
                    'tax',
                    'tax_pct',
                    'total_cost',
                    'payment_code',
                    'filled_at',
                    'verified_at',
                    'rejected_at',
                    'expired_at',
                    'completed_at',
                    'complianced_at',
                    'status',
                    'rejection_reason',
                    'observation',
                    'created_at',
                    'updated_at',
                ]
            ]);
    }

    /** @test */
    public function a_compliance_user_can_view_a_single_order_for_admin()
    {
        $user = User::factory()->create();
        $user->assignRole(['base', 'compliance']);
        Sanctum::actingAs($user);

        $order = Order::factory()->create();

        $this->json('get', '/api/admin/orders/' . $order->id)
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'user_id',
                    'recipient' => [],
                    'pair' => [],
                    'priority' => [],
                    'payment' => [],
                    'sended_amount',
                    'received_amount',
                    'rate',
                    'payment_amount',
                    'transaction_cost',
                    'priority_cost',
                    'tax',
                    'tax_pct',
                    'total_cost',
                    'payment_code',
                    'filled_at',
                    'verified_at',
                    'rejected_at',
                    'expired_at',
                    'completed_at',
                    'complianced_at',
                    'status',
                    'rejection_reason',
                    'observation',
                    'created_at',
                    'updated_at',
                ],
            ]);
    }

    /** @test */
    public function non_authenticated_user_cannot_verify_payment()
    {
        $this->json('post', '/api/admin/orders/100/verify-payment')
            ->assertUnauthorized();
    }

    /** @test */
    public function cannot_verify_payment_to_non_existing_order()
    {
        $user = User::factory()->create();
        $user->assignRole(['user', 'base', 'admin', 'super_admin', 'agent']);
        Sanctum::actingAs($user);

        $this->json('post', '/api/admin/orders/100/verify-payment')
            ->assertNotFound();
    }

    /** @test */
    public function non_compliance_cannot_verify_payment()
    {
        $user = User::factory()->create();
        $user->assignRole(['user', 'base', 'admin', 'super_admin', 'agent']);
        Sanctum::actingAs($user);

        $order = Order::factory()->create();

        $this->json('post', '/api/admin/orders/' . $order->id . '/verify-payment')
            ->assertForbidden();
    }

    /** @test */
    public function a_compliance_user_can_verify_payment_of_an_order()
    {
        $user = User::factory()->create();
        $user->assignRole(['user', 'compliance']);
        Sanctum::actingAs($user);

        $order = Order::factory()->create();
        Payment::factory()->create([
            'order_id' => $order->id
        ]);

        $this->json('post', '/api/admin/orders/' . $order->id . '/verify-payment')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'user_id',
                    'recipient' => [],
                    'pair' => [],
                    'priority' => [],
                    'payment' => [],
                    'sended_amount',
                    'received_amount',
                    'rate',
                    'payment_amount',
                    'transaction_cost',
                    'priority_cost',
                    'tax',
                    'tax_pct',
                    'total_cost',
                    'payment_code',
                    'filled_at',
                    'verified_at',
                    'rejected_at',
                    'expired_at',
                    'completed_at',
                    'complianced_at',
                    'status',
                    'rejection_reason',
                    'observation',
                    'created_at',
                    'updated_at',
                ],
            ]);
    }

    /** @test */
    public function cannot_verify_payment_that_does_not_exists_in_the_order()
    {
        $user = User::factory()->create();
        $user->assignRole(['user', 'compliance']);
        Sanctum::actingAs($user);

        $order = Order::factory()->create();

        $this->json('post', '/api/admin/orders/' . $order->id . '/verify-payment')
            ->assertForbidden();
    }

    /** @test */
    public function cannot_verify_a_payment_that_is_already_rejected()
    {
        $user = User::factory()->create();
        $user->assignRole(['user', 'compliance']);
        Sanctum::actingAs($user);

        $order = Order::factory()->create();
        Payment::factory()->create([
            'order_id' => $order->id,
            'rejected_at' => now()->subHour()
        ]);

        $this->json('post', '/api/admin/orders/' . $order->id . '/verify-payment')
            ->assertForbidden();
    }

    /** @test */
    public function cannot_verify_a_payment_that_is_already_verified()
    {
        $user = User::factory()->create();
        $user->assignRole(['user', 'compliance']);
        Sanctum::actingAs($user);

        $order = Order::factory()->create();
        Payment::factory()->create([
            'order_id' => $order->id,
            'verified_at' => now()->subHour()
        ]);

        $this->json('post', '/api/admin/orders/' . $order->id . '/verify-payment')
            ->assertForbidden();
    }

    /** @test */
    public function cannot_verified_a_payment_if_the_order_has_been_rejected_or_expired()
    {
        $user = User::factory()->create();
        $user->assignRole(['user', 'compliance']);
        Sanctum::actingAs($user);

        $order = Order::factory()->create([
            'rejected_at' => now()->subHour()
        ]);
        Payment::factory()->create([
            'order_id' => $order->id,
        ]);

        $this->json('post', '/api/admin/orders/' . $order->id . '/verify-payment')
            ->assertForbidden();

        $order = Order::factory()->create([
            'expired_at' => now()->subHour()
        ]);
        Payment::factory()->create([
            'order_id' => $order->id,
        ]);

        $this->json('post', '/api/admin/orders/' . $order->id . '/verify-payment')
            ->assertForbidden();
    }

    /** @test */
    public function non_authenticated_user_cannot_reject_a_payment_for_an_order()
    {
        $this->json('post', '/api/admin/orders/100/reject-payment')
            ->assertUnauthorized();
    }

    /** @test */
    public function cannot_reject_payment_of_non_existing_order()
    {
        $user = User::factory()->create();
        $user->assignRole(['user', 'base', 'admin', 'super_admin', 'agent']);
        Sanctum::actingAs($user);

        $this->json('post', '/api/admin/orders/100/reject-payment')
            ->assertNotFound();
    }

    /** @test */
    public function non_compliance_user_cannot_reject_payment_for_an_order()
    {
        $user = User::factory()->create();
        $user->assignRole(['user', 'base', 'admin', 'super_admin', 'agent']);
        Sanctum::actingAs($user);

        $order = Order::factory()->create();

        $this->json('post', '/api/admin/orders/' . $order->id . '/reject-payment')
            ->assertForbidden();
    }

    /** @test */
    public function a_compliance_user_can_reject_a_payment_to_an_order()
    {
        $user = User::factory()->create();
        $user->assignRole(['base', 'compliance']);
        Sanctum::actingAs($user);

        $order = Order::factory()->create([
            'rejection_reason' => null
        ]);
        Payment::factory()->create([
            'order_id' => $order->id
        ]);

        $this->json('post', '/api/admin/orders/' . $order->id . '/reject-payment', ['observation' => 'lorem ipsum' ])
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'user_id',
                    'recipient' => [],
                    'pair' => [],
                    'priority' => [],
                    'payment' => [],
                    'sended_amount',
                    'received_amount',
                    'rate',
                    'payment_amount',
                    'transaction_cost',
                    'priority_cost',
                    'tax',
                    'tax_pct',
                    'total_cost',
                    'payment_code',
                    'filled_at',
                    'verified_at',
                    'rejected_at',
                    'expired_at',
                    'completed_at',
                    'complianced_at',
                    'status',
                    'rejection_reason',
                    'observation',
                    'created_at',
                    'updated_at',
                ],
            ])
            ->assertJsonFragment([
                'id' => $order->id,
                'rejection_reason' => 'lorem ipsum'
            ]);

        $order->refresh();
        $this->assertNotNull($order->rejected_at);
        $this->assertNotNull($order->payment->rejected_at);
    }

    /** @test */
    public function cannot_reject_a_payment_that_does_not_exists()
    {
        $user = User::factory()->create();
        $user->assignRole(['base', 'compliance']);
        Sanctum::actingAs($user);

        $order = Order::factory()->create();

        $this->json('post', '/api/admin/orders/' . $order->id . '/reject-payment', ['rejection_reason' => 'lorem ipsum' ])
            ->assertForbidden();
    }

    /** @test */
    public function cannot_reject_a_payment_that_is_already_reject_and_cannot_reject_a_payment_that_is_already_verifed()
    {
        $user = User::factory()->create();
        $user->assignRole(['user', 'compliance']);
        Sanctum::actingAs($user);

        $order = Order::factory()->create();
        Payment::factory()->create([
            'order_id' => $order->id,
            'rejected_at' => now()->subHour()
        ]);

        $this->json('post', '/api/admin/orders/' . $order->id . '/reject-payment')
            ->assertForbidden();

        $order = Order::factory()->create();
        Payment::factory()->create([
            'order_id' => $order->id,
            'verified_at' => now()->subHour()
        ]);

        $this->json('post', '/api/admin/orders/' . $order->id . '/reject-payment')
            ->assertForbidden();
    }

    /** @test */
    public function cannot_reject_a_payment_if_the_order_has_been_rejected_or_expired()
    {
        $user = User::factory()->create();
        $user->assignRole(['user', 'compliance']);
        Sanctum::actingAs($user);

        $order = Order::factory()->create([
            'rejected_at' => now()->subHour()
        ]);
        Payment::factory()->create([
            'order_id' => $order->id,
        ]);

        $this->json('post', '/api/admin/orders/' . $order->id . '/reject-payment')
            ->assertForbidden();

        $order = Order::factory()->create([
            'expired_at' => now()->subHour()
        ]);
        Payment::factory()->create([
            'order_id' => $order->id,
        ]);

        $this->json('post', '/api/admin/orders/' . $order->id . '/reject-payment')
            ->assertForbidden();
    }

    /** @test */
    public function non_authenticated_user_cannot_verify_a_order()
    {
        $this->json('post', '/api/admin/orders/100/verify-order')
            ->assertUnauthorized();
    }

    /** @test */
    public function cannot_verify_non_existing_order()
    {
        $user = User::factory()->create();
        $user->assignRole(['user', 'base', 'admin', 'super_admin', 'agent']);
        Sanctum::actingAs($user);

        $this->json('post', '/api/admin/orders/100/verify-order')
            ->assertNotFound();
    }

    /** @test */
    public function no_compliance_user_cannot_verify_an_order()
    {
        $user = User::factory()->create();
        $user->assignRole(['user', 'base', 'admin', 'super_admin', 'agent']);
        Sanctum::actingAs($user);

        $order = Order::factory()->create();

        $this->json('post', '/api/admin/orders/' . $order->id . '/verify-order')
            ->assertForbidden();
    }

    /** @test */
    public function a_compliance_user_can_verify_an_order()
    {
        $user = User::factory()->create();
        $user->assignRole(['base', 'compliance']);
        Sanctum::actingAs($user);

        $order = Order::factory()->create();
        Payment::factory()->create([
            'order_id' => $order->id,
            'verified_at' => now(),
        ]);

        $this->json('post', '/api/admin/orders/' . $order->id . '/verify-order')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'user_id',
                    'recipient' => [],
                    'pair' => [],
                    'priority' => [],
                    'payment' => [],
                    'sended_amount',
                    'received_amount',
                    'rate',
                    'payment_amount',
                    'transaction_cost',
                    'priority_cost',
                    'tax',
                    'tax_pct',
                    'total_cost',
                    'payment_code',
                    'filled_at',
                    'verified_at',
                    'rejected_at',
                    'expired_at',
                    'completed_at',
                    'complianced_at',
                    'status',
                    'rejection_reason',
                    'observation',
                    'created_at',
                    'updated_at',
                ],
            ]);

        $order->refresh();
        $this->assertNotNull($order->verified_at);
    }

    /** @test */
    public function cannot_verify_an_order_if_the_payment_has_not_been_verfied()
    {
        $user = User::factory()->create();
        $user->assignRole(['base', 'compliance']);
        Sanctum::actingAs($user);

        $order = Order::factory()->create();
        Payment::factory()->create([
            'order_id' => $order->id,
            'verified_at' => null,
        ]);

        $this->json('post', '/api/admin/orders/' . $order->id . '/verify-order')
            ->assertForbidden();
    }

    /** @test */
    public function cannot_verify_an_order_if_the_order_is_rejected_or_is_already_verified_or_completed_at_or_expired_at()
    {
        $user = User::factory()->create();
        $user->assignRole(['base', 'compliance']);
        Sanctum::actingAs($user);

        $order = Order::factory()->create([
            'rejected_at' => now()->subHour()
        ]);
        Payment::factory()->create([
            'order_id' => $order->id,
            'verified_at' => now(),
        ]);

        $this->json('post', '/api/admin/orders/' . $order->id . '/verify-order')
            ->assertForbidden();

        $order = Order::factory()->create([
            'verified_at' => now()->subHour()
        ]);
        Payment::factory()->create([
            'order_id' => $order->id,
            'verified_at' => now(),
        ]);

        $this->json('post', '/api/admin/orders/' . $order->id . '/verify-order')
            ->assertForbidden();

        $order = Order::factory()->create([
            'completed_at' => now()->subHour()
        ]);
        Payment::factory()->create([
            'order_id' => $order->id,
            'verified_at' => now(),
        ]);

        $this->json('post', '/api/admin/orders/' . $order->id . '/verify-order')
            ->assertForbidden();

        $order = Order::factory()->create([
            'expired_at' => now()->subHour()
        ]);
        Payment::factory()->create([
            'order_id' => $order->id,
            'verified_at' => now(),
        ]);

        $this->json('post', '/api/admin/orders/' . $order->id . '/verify-order')
            ->assertForbidden();
    }

    /** @test */
    public function non_authenticated_user_cannot_reject_an_order()
    {
        $this->json('post', '/api/admin/orders/100/reject-order')
            ->assertUnauthorized();
    }

    /** @test */
    public function cannot_reject_non_existing_order()
    {
        $user = User::factory()->create();
        $user->assignRole(['user', 'base', 'admin', 'super_admin', 'agent']);
        Sanctum::actingAs($user);

        $this->json('post', '/api/admin/orders/100/reject-order')
            ->assertNotFound();
    }

    /** @test */
    public function non_compliance_user_cannot_reject_an_order()
    {
        $user = User::factory()->create();
        $user->assignRole(['user', 'base', 'admin', 'super_admin', 'agent']);
        Sanctum::actingAs($user);

        $order = Order::factory()->create();

        $this->json('post', '/api/admin/orders/' . $order->id . '/reject-order')
            ->assertForbidden();
    }

    /** @test */
    public function a_compliance_user_can_reject_an_order()
    {
        $user = User::factory()->create();
        $user->assignRole(['base', 'compliance']);
        Sanctum::actingAs($user);

        $order = Order::factory()->create();
        Payment::factory()->create([
            'order_id' => $order->id,
            'verified_at' => now()
        ]);

        $this->json('post', '/api/admin/orders/' . $order->id . '/reject-order')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'user_id',
                    'recipient' => [],
                    'pair' => [],
                    'priority' => [],
                    'payment' => [],
                    'sended_amount',
                    'received_amount',
                    'rate',
                    'payment_amount',
                    'transaction_cost',
                    'priority_cost',
                    'tax',
                    'tax_pct',
                    'total_cost',
                    'payment_code',
                    'filled_at',
                    'verified_at',
                    'rejected_at',
                    'expired_at',
                    'completed_at',
                    'complianced_at',
                    'status',
                    'rejection_reason',
                    'observation',
                    'created_at',
                    'updated_at',
                ],
            ]);

        $order->refresh();
        $this->assertNotNull($order->rejected_at);
    }

    /** @test */
    public function can_refund_money_if_the_payment_has_been_made_with_balance_when_the_order_has_been_rejected()
    {
        $user = User::factory()->create();
        $user->assignRole(['base', 'compliance']);
        Sanctum::actingAs($user);


        $client = User::factory()->create(['balance' => 0]);
        $order = Order::factory()->create([
            'user_id' => $client->id,
            'payment_amount' => 50000
        ]);
        $paymentType = PaymentType::where('name','balance_payment')->firstOrFail();
        Payment::factory()->create([
            'order_id' => $order->id,
            'payment_type_id' => $paymentType->id,
            'verified_at' => now()
        ]);

        $this->json('post', '/api/admin/orders/' . $order->id . '/reject-order')
            ->assertOk();

        $order->refresh();
        $this->assertNotNull($order->rejected_at);
        $client->refresh();
        $this->assertEquals(50000, $client->balance);
    }

    /** @test */
    public function cannot_reject_an_order_if_this_is_not_already_rejected_or_completed_or_expired_at()
    {
        $user = User::factory()->create();
        $user->assignRole(['base', 'compliance']);
        Sanctum::actingAs($user);

        $order = Order::factory()->create([
            'rejected_at' => now()->subHour()
        ]);

        $this->json('post', '/api/admin/orders/' . $order->id . '/reject-order')
            ->assertForbidden();

        $order = Order::factory()->create([
            'completed_at' => now()->subHour()
        ]);

        $this->json('post', '/api/admin/orders/' . $order->id . '/verify-order')
            ->assertForbidden();

        $order = Order::factory()->create([
            'expired_at' => now()->subHour()
        ]);

        $this->json('post', '/api/admin/orders/' . $order->id . '/verify-order')
            ->assertForbidden();
    }

    /** @test */
    public function non_authenticated_user_cannot_pay_with_balance()
    {
        $this->json('post', '/api/orders/100/pay-with-balance')
            ->assertUnauthorized();
    }

    /** @test */
    public function cannot_pay_with_balance_non_existing_order()
    {
        $user = User::factory()->create();
        $user->assignRole(['user']);
        Sanctum::actingAs($user);

        $this->json('post', '/api/orders/100/pay-with-balance')
            ->assertNotFound();
    }

    /** @test */
    public function admin_user_cannot_pay_with_balance_an_order()
    {
        $user = User::factory()->create();
        $user->assignRole(['base', 'admin', 'super_admin', 'compliance']);
        Sanctum::actingAs($user);

        $order = Order::factory()->create();

        $this->json('post', '/api/orders/' . $order->id . '/pay-with-balance')
            ->assertForbidden();
    }

    /** @test */
    public function an_user_cannot_pay_with_balance_that_belongs_to_other_user()
    {
        $user = User::factory()->create();
        $user->assignRole(['user']);
        Sanctum::actingAs($user);

        $order = Order::factory()->create();

        $this->json('post', '/api/orders/' . $order->id . '/pay-with-balance')
            ->assertForbidden();
    }

    /** @test */
    public function an_user_can_pay_with_balance()
    {
        $user = User::factory()->create(['balance' => 20000]);
        $user->assignRole(['user']);
        Sanctum::actingAs($user);

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'payment_amount' => 15000
        ]);

        $this->json('post', '/api/orders/' . $order->id . '/pay-with-balance')
            ->assertOk();

        $user->refresh();
        $this->assertEquals(5000, $user->balance);

        $order->refresh();
        $this->assertNotNull($order->payment);
        $this->assertNotNull($order->filled_at);
        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'user_id' => $user->id,
            'payment_amount' => $order->payment_amount
        ]);
    }

    /** @test */
    public function an_user_cannot_pay_with_balance_does_not_have_enough_balance()
    {
        $user = User::factory()->create(['balance' => 0]);
        $user->assignRole(['user']);
        Sanctum::actingAs($user);

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'payment_amount' => 15000
        ]);

        $this->json('post', '/api/orders/' . $order->id . '/pay-with-balance')
            ->assertForbidden();

        $user->refresh();
        $this->assertEquals(0, $user->balance);

        $order->refresh();
        $this->assertNull($order->payment);
        $this->assertNull($order->filled_at);
    }

    /** @test */
    public function an_user_can_pay_with_balance_if_the_user_has_no_balance_but_has_credit()
    {
        $user = User::factory()->create(['balance' => 0, 'balance_credit_limit' => 20000]);
        $user->assignRole(['user']);
        Sanctum::actingAs($user);

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'payment_amount' => 15000
        ]);

        $this->json('post', '/api/orders/' . $order->id . '/pay-with-balance')
            ->assertOk();

        $user->refresh();
        $this->assertEquals(-15000, $user->balance);

        $order->refresh();
        $this->assertNotNull($order->payment);
        $this->assertNotNull($order->filled_at);
        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'user_id' => $user->id,
            'payment_amount' => $order->payment_amount
        ]);
    }

    /** @test */
    public function cannot_pay_with_balance_if_the_payment_amount_is_greater_than_credit()
    {
        $user = User::factory()->create(['balance' => 10000, 'balance_credit_limit' => 20000]);
        $user->assignRole(['user']);
        Sanctum::actingAs($user);

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'payment_amount' => 50000
        ]);

        $this->json('post', '/api/orders/' . $order->id . '/pay-with-balance')
            ->assertForbidden();

        $user->refresh();
        $this->assertEquals(10000, $user->balance);
        $this->assertEquals(20000, $user->balance_credit_limit);

        $order->refresh();
        $this->assertNull($order->payment);
        $this->assertNull($order->filled_at);
    }

    /** @test */
    public function cannot_pay_with_balance_if_the_order_has_already_a_payment_or_filled()
    {
        $user = User::factory()->create(['balance' => 10000]);
        $user->assignRole(['user']);
        Sanctum::actingAs($user);

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'payment_amount' => 10000,
            'filled_at' => now()
        ]);

        Payment::factory()->create([
            'order_id' => $order->id,
            'payment_amount' => 10000
        ]);

        $this->json('post', '/api/orders/' . $order->id . '/pay-with-balance')
            ->assertForbidden();

        $user->refresh();
        $this->assertEquals(10000, $user->balance);
    }
}
