<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\PaymentType;
use Laravel\Sanctum\Sanctum;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class PaymentTypeTest extends TestCase
{
    use DatabaseMigrations, RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    /** @test */
    public function non_authenticated_user_cannot_view_index_of_payments_types()
    {
        $this->json('get', '/api/payment-types')
            ->assertUnauthorized();
    }

    /** @test */
    public function an_authenticated_user_can_view_the_index_of_payment_types()
    {
        $user = User::factory()->create();
        $user->assignRole(['user', 'base', 'admin', 'super_admin', 'compliance', 'agent']);
        Sanctum::actingAs($user);

        PaymentType::factory()->count(10)->create();
        PaymentType::factory()->count(5)->create([ 'is_active' => false ]);

        $this->json('get', '/api/payment-types')
            ->assertOk()
            ->assertJsonCount(10, 'data')
            ->assertJsonStructure([
                'data' => [
                    [
                        'name',
                        'label',
                        'description',
                        'class_name',
                        'is_active',
                        'updated_at',
                        'created_at',
                    ]
                ]
            ]);
    }

    /** @test */
    public function non_authenticated_user_cannot_view_admin_index_of_payment_types()
    {
        $this->json('get', '/api/admin/payment-types')
            ->assertUnauthorized();
    }

    /** @test */
    public function non_admin_user_cannot_view_admin_index_of_payment_types()
    {
        $user = User::factory()->create();
        $user->assignRole(['user', 'base', 'super_admin', 'compliance', 'agent']);
        Sanctum::actingAs($user);

        $this->json('get', '/api/admin/payment-types')
            ->assertForbidden();
    }

    /** @test */
    public function an_admin_user_can_view_index_for_admin_of_payment_types()
    {
        $user = User::factory()->create();
        $user->assignRole(['base', 'admin']);
        Sanctum::actingAs($user);

        PaymentType::factory()->count(10)->create();
        PaymentType::factory()->count(5)->create([ 'is_active' => false ]);

        $this->json('get', '/api/admin/payment-types')
        ->assertOk()
        ->assertJsonCount(15, 'data')
        ->assertJsonStructure([
            'data' => [
                [
                    'name',
                    'label',
                    'description',
                    'class_name',
                    'is_active',
                    'updated_at',
                    'created_at',
                ]
            ]
        ]);
    }

    /** @test */
    public function non_authenticated_user_cannot_create_a_payment_type()
    {
        $this->json('post', '/api/admin/payment-types')
            ->assertUnauthorized();
    }

    /** @test */
    public function non_admin_user_cannot_create_a_payment_type()
    {
        $user = User::factory()->create();
        $user->assignRole(['user', 'base', 'super_admin', 'compliance', 'agent']);
        Sanctum::actingAs($user);

        $this->json('post', '/api/admin/payment-types')
            ->assertForbidden();
    }

    /** @test */
    public function an_admin_user_can_create_a_payment()
    {
        $user = User::factory()->create();
        $user->assignRole(['base', 'admin']);
        Sanctum::actingAs($user);

        $paymentType = PaymentType::factory()->raw();

        $this->json('post', '/api/admin/payment-types', $paymentType)
            ->assertCreated();

        $this->assertDatabaseHas('payment_types', [
            'name'          => $paymentType['name'],
            'label'         => $paymentType['label'],
            'description'   => $paymentType['description'],
            'is_active'     => $paymentType['is_active'],
            'class_name'    => $paymentType['class_name']
        ]);
    }

    /** @test */
    public function cannot_create_a_payment_type_without_required_fields()
    {
        $user = User::factory()->create();
        $user->assignRole(['base', 'admin']);
        Sanctum::actingAs($user);

        $paymentType = PaymentType::factory()->raw([
            'name'          => null,
            'label'         => null,
            'description'   => null,
            'is_active'     => null,
            'class_name'    => null,
        ]);

        $this->json('post', '/api/admin/payment-types', $paymentType)
            ->assertStatus(422)
            ->assertJsonFragment(['The name field is required.'])
            ->assertJsonFragment(['The label field is required.']);
    }

    /** @test */
    public function non_authenticated_user_cannot_update_a_payment_type()
    {
        $this->json('put', '/api/admin/payment-types/100')
            ->assertUnauthorized();
    }

    /** @test */
    public function cannot_update_non_existing_payment_type()
    {
        $user = User::factory()->create();
        $user->assignRole(['user', 'base', 'super_admin', 'compliance', 'agent']);
        Sanctum::actingAs($user);

        $this->json('put', '/api/admin/payment-types/100')
            ->assertNotFound();
    }

    /** @test */
    public function admin_user_can_update_a_document_type_but_cannot_update_the_name()
    {
        $user = User::factory()->create();
        $user->assignRole(['base', 'admin']);
        Sanctum::actingAs($user);

        $paymentType = PaymentType::factory()->create();

        $this->json('put', '/api/admin/payment-types/' . $paymentType->id, [
            'name'          => 'no_name',
            'label'         => 'new_label',
            'description'   => 'new description',
            'class_name'    => 'another class name',
            'is_active'     => false
        ])
            ->assertOk()
            ->assertJsonFragment([
                'id'            => $paymentType->id,
                'name'          => $paymentType->name,
                'label'         => 'new_label',
                'description'   => 'new description',
                'class_name'    => 'another class name',
                'is_active'     => false
            ]);

            $this->assertDatabaseHas('payment_types', [
                'id'            => $paymentType->id,
                'name'          => $paymentType->name,
                'label'         => 'new_label',
                'description'   => 'new description',
                'class_name'    => 'another class name',
                'is_active'     => false
            ]);

            $this->assertDatabaseMissing('payment_types', [
                'name' => 'no_name'
            ]);
    }

    /** @test */
    public function non_authenticated_user_cannot_view_a_single_payment_type()
    {
        $this->json('get', '/api/admin/payment-types/100')
            ->assertUnauthorized();
    }

    /** @test */
    public function cannot_view_non_existing_single_payment_type()
    {
        $user = User::factory()->create();
        $user->assignRole(['user', 'base', 'super_admin', 'compliance', 'agent']);
        Sanctum::actingAs($user);

        $this->json('get', '/api/admin/payment-types/100')
            ->assertNotFound();
    }

    /** @test */
    public function non_admin_user_cannot_view_single_payment_type()
    {
        $user = User::factory()->create();
        $user->assignRole(['base', 'admin']);
        Sanctum::actingAs($user);

        $paymentType = PaymentType::factory()->create();

        $this->json('get', '/api/admin/payment-types/' . $paymentType->id)
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'label',
                    'description',
                    'class_name',
                    'is_active',
                ]
            ]);
    }

    /** @test */
    public function non_authenticated_user_cannot_delete_a_payment_type()
    {
        $this->json('delete', '/api/admin/payment-types/100')
            ->assertUnauthorized();
    }

    /** @test */
    public function cannot_delete_non_existing_payment_type()
    {
        $user = User::factory()->create();
        $user->assignRole(['user', 'base', 'super_admin', 'compliance', 'agent']);
        Sanctum::actingAs($user);

        $this->json('delete', '/api/admin/payment-types/100')
            ->assertNotFound();
    }

    /** @test */
    public function non_admin_user_cannot_delete_a_payment_type()
    {
        $user = User::factory()->create();
        $user->assignRole(['user', 'base', 'super_admin', 'compliance', 'agent']);
        Sanctum::actingAs($user);

        $paymentType = PaymentType::factory()->create();

        $this->json('delete', '/api/admin/payment-types/' . $paymentType->id)
            ->assertForbidden();

        $this->assertDatabaseHas('payment_types', [
            'id'            => $paymentType->id,
            'name'          => $paymentType->name,
        ]);
    }

    /** @test */
    public function an_admin_user_can_delete_a_payment_type()
    {
        $user = User::factory()->create();
        $user->assignRole(['base', 'admin']);
        Sanctum::actingAs($user);

        $paymentType = PaymentType::factory()->create();

        $this->json('delete', '/api/admin/payment-types/' . $paymentType->id)
            ->assertOk();

        $this->assertDatabaseMissing('payment_types', [
            'id'            => $paymentType->id,
            'name'          => $paymentType->name,
        ]);
    }
}
