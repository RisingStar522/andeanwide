<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Order;
use App\Models\Comment;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Event;
use App\Events\PayoutNotificationArrived;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class CommentTest extends TestCase
{
    use DatabaseMigrations, RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    /** @test */
    public function non_authenticated_user_cannot_view_orders_comments()
    {
        $this->json('get', '/api/admin/orders/100/comments')
            ->assertUnauthorized();
    }

    /** @test */
    public function cannot_view_orders_comments_if_the_orders_not_exists()
    {
        $user = User::factory()->create();
        $user->assignRole(['user', 'base', 'super_admin', 'compliance', 'agent']);
        Sanctum::actingAs($user);

        $this->json('get', '/api/admin/orders/100/comments')
            ->assertNotFound();
    }

    /** @test */
    public function user_with_no_admin_role_cannot_view_orders_comments()
    {
        $user = User::factory()->create();
        $user->assignRole(['user', 'base', 'super_admin', 'compliance', 'agent']);
        Sanctum::actingAs($user);

        $order = Order::factory()->create();

        $this->json('get', '/api/admin/orders/' . $order->id . '/comments')
            ->assertForbidden();
    }

    /** @test */
    public function admin_user_can_view_orders_comment()
    {
        $user = User::factory()->create();
        $user->assignRole(['admin']);
        Sanctum::actingAs($user);

        $order = Order::factory()->create();
        Comment::factory()->count(10)->create([
            'commentable_id' => $order->id,
            'commentable_type' => 'App\Models\Order'
        ]);

        $this->json('get', '/api/admin/orders/' . $order->id . '/comments')
            ->assertOk()
            ->assertJsonCount(10, 'data')
            ->assertJsonStructure([
                'data' => [
                    [
                        'id',
                        'comments',
                        'cashout_id',
                        'date',
                        'bank_reference_id',
                        'commentable_id',
                        'commentable_type',
                        'created_at',
                        'updated_at'
                    ]
                ],
            ]);
    }

    /** @test */
    public function can_create_a_comment_from_dlocal_notification_post_request_an_update_the_order_status()
    {
        Event::fake();
        $order = Order::factory()->create([
            'payout_id' => '1234',
            'payed_at' => '2021/01/01',
            'payout_status' => 'Received',
            'payout_status_code' => 0
        ]);
        $request_data = [
            'external_id' => 'AW' . Str::padLeft($order->id, 6, '0'),
            'cashout_id' => '1234',
            'date' => '2021/01/02',
            'bank_reference_id' => 'ABCD12345',
            'comments' => 'lorem ipsum'
        ];

        $this->json('post', 'api/orders/comments', $request_data)
            ->assertCreated();

        Event::assertDispatched(PayoutNotificationArrived::class);

        $this->assertDatabaseHas('comments', [
            'cashout_id' => '1234',
            'bank_reference_id' => 'ABCD12345',
            'comments' => 'lorem ipsum',
            'commentable_id' => $order->id,
            'commentable_type' => 'App\\Models\\Order'
        ]);
    }

    /** @test */
    public function cannot_create_a_comment_of_non_existing_order()
    {
        $request_data = [
            'external_id' => 'AW0000100',
            'cashout_id' => '1234',
            'date' => '2021/01/02',
            'bank_reference_id' => 'ABCD12345',
            'comments' => 'lorem ipsum'
        ];

        $this->json('post', 'api/orders/comments', $request_data)
            ->assertNotFound();

        $order = Order::factory()->create([
            'payout_id' => '1234',
            'payed_at' => '2021/01/01',
            'payout_status' => 'Received',
            'payout_status_code' => 0
        ]);
        $request_data = [
            'external_id' => 'AW' . Str::padLeft($order->id, 6, '0'),
            'cashout_id' => '12345678',
            'date' => '2021/01/02',
            'bank_reference_id' => 'ABCD12345',
            'comments' => 'lorem ipsum'
        ];

        $this->json('post', 'api/orders/comments', $request_data)
            ->assertNotFound();
    }
}
