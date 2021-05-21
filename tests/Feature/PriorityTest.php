<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Priority;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class PriorityTest extends TestCase
{
    use DatabaseMigrations, RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        Role::create(['name' => 'user']);
        Role::create(['name' => 'base']);
        Role::create(['name' => 'agent']);
        Role::create(['name' => 'admin']);
        Role::create(['name' => 'compliance']);
        Role::create(['name' => 'super_admin']);
    }

    /** @test */
    public function non_authenticated_user_can_view_index_of_priorities()
    {
        Priority::factory()->count(10)->create();
        Priority::factory()->count(5)->create([
            'is_active' => false
        ]);

        $this->json('get', 'api/priorities')
            ->assertOk()
            ->assertJsonCount(10, 'data')
            ->assertJsonStructure([
                'data' => [
                    [
                        'id',
                        'name',
                        'label',
                        'sublabel',
                        'description',
                        'cost_pct',
                        'is_active'
                    ]
                ]
            ]);
    }

    /** @test */
    public function regular_user_can_view_index_of_priorities()
    {
        $user = User::factory()->create();
        $user->assignRole(['user']);
        Sanctum::actingAs($user);

        Priority::factory()->count(10)->create();
        Priority::factory()->count(5)->create([ 'is_active' => false ]);

        $this->json('get', 'api/priorities')
            ->assertOK()
            ->assertJsonCount(10, 'data');
    }

    /** @test */
    public function admin_users_can_view_index_of_priorities()
    {
        $user = User::factory()->create();
        $user->assignRole(['base', 'admin', 'super_admin', 'compliance', 'agent']);
        Sanctum::actingAs($user);

        Priority::factory()->count(10)->create();
        Priority::factory()->count(5)->create([ 'is_active' => false ]);

        $this->json('get', 'api/priorities')
            ->assertOk()
            ->assertJsonCount(10, 'data');
    }

    /** @test */
    public function non_athenticated_user_cannot_view_a_single_priority()
    {
        $this->json('get', 'api/admin/priorities/100')
            ->assertUnauthorized();
    }

    /** @test */
    public function cannot_view_non_existing_priority()
    {
        $user = User::factory()->create();
        $user->assignRole(['base', 'admin']);
        Sanctum::actingAs($user);

        $this->json('get', 'api/admin/priorities/100')
            ->assertNotFound();
    }

    /** @test */
    public function non_admin_user_cannot_view_priority()
    {
        $user = User::factory()->create();
        $user->assignRole(['base', 'user', 'super_admin', 'compliance', 'agent']);
        Sanctum::actingAs($user);

        $priority = Priority::factory()->create();

        $this->json('get', 'api/admin/priorities/' . $priority->id)
            ->assertForbidden();
    }

    /** @test */
    public function an_admin_user_can_view_a_priority()
    {
        $user = User::factory()->create();
        $user->assignRole(['base', 'admin']);
        Sanctum::actingAs($user);

        $priority = Priority::factory()->create();

        $this->json('get', 'api/admin/priorities/' . $priority->id)
            ->assertOk()
            ->assertJsonFragment(['id' => $priority->id])
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'label',
                    'sublabel',
                    'description',
                    'cost_pct',
                    'is_active'
                ]
            ]);
    }

    /** @test */
    public function non_authenticated_user_cannot_create_a_priority()
    {
        $this->json('post', 'api/admin/priorities')
            ->assertUnauthorized();
    }

    /** @test */
    public function user_with_no_admin_role_cannot_create_a_priority()
    {
        $user = User::factory()->create();
        $user->assignRole(['base', 'user', 'super_admin', 'compliance', 'agent']);
        Sanctum::actingAs($user);

        $this->json('post', 'api/admin/priorities')
            ->assertForbidden();
    }

    /** @test */
    public function an_admin_user_can_create_a_priority()
    {
        $user = User::factory()->create();
        $user->assignRole(['base', 'admin']);
        Sanctum::actingAs($user);

        $priority = Priority::factory()->raw();

        $this->json('post', 'api/admin/priorities', $priority)
            ->assertCreated();

        $this->assertDatabaseHas('priorities', [
            'name'          => $priority['name'],
            'label'         => $priority['label'],
            'sublabel'      => $priority['sublabel'],
            'description'   => $priority['description'],
            'is_active'     => true
        ]);
    }

    /** @test */
    public function cannot_create_a_priority_without_the_required_fields()
    {
        $user = User::factory()->create();
        $user->assignRole(['base', 'admin']);
        Sanctum::actingAs($user);

        $priority = Priority::factory()->raw([
            'name'  => null,
            'label' => null,
        ]);

        $this->json('post', 'api/admin/priorities', $priority)
            ->assertStatus(422)
            ->assertJsonFragment(['The name field is required.'])
            ->assertJsonFragment(['The label field is required.']);
    }

    /** @test */
    public function cannot_create_a_priority_with_cost_pct_value_is_not_numeric()
    {
        $user = User::factory()->create();
        $user->assignRole(['base', 'admin']);
        Sanctum::actingAs($user);

        $priority = Priority::factory()->raw([
            'cost_pct' => 'string',
        ]);

        $this->json('post', 'api/admin/priorities', $priority)
            ->assertStatus(422)
            ->assertJsonFragment(['The cost pct must be a number.']);
    }

    /** @test */
    public function non_authenticated_user_cannot_update_a_priority()
    {
        $this->json('put', 'api/admin/priorities/100')
            ->assertUnauthorized();
    }

    /** @test */
    public function cannot_update_a_non_existing_priority()
    {
        $user = User::factory()->create();
        $user->assignRole(['base', 'user', 'super_admin', 'compliance', 'agent']);
        Sanctum::actingAs($user);

        $this->json('put', 'api/admin/priorities/100')
            ->assertNotFound();
    }

    /** @test */
    public function non_admin_user_cannot_update_apriority()
    {
        $user = User::factory()->create();
        $user->assignRole(['base', 'user', 'super_admin', 'compliance', 'agent']);
        Sanctum::actingAs($user);

        $priority = Priority::factory()->create();

        $this->json('put', 'api/admin/priorities/' . $priority->id)
            ->assertForbidden();
    }

    /** @test */
    public function an_admin_user_can_update_a_priority()
    {
        $user = User::factory()->create();
        $user->assignRole(['base', 'admin']);
        Sanctum::actingAs($user);

        $priority = Priority::factory()->create();

        $this->json('put', 'api/admin/priorities/' . $priority->id, [
            'name'          => 'new name',
            'label'         => 'new label',
            'sublabel'      => 'new sublabel',
            'description'   => 'new description',
            'cost_pct'      => 10,
            'is_active'     => false
        ])->assertOK();

        $this->assertDatabaseHas('priorities', [
            'name'          => 'new name',
            'label'         => 'new label',
            'sublabel'      => 'new sublabel',
            'description'   => 'new description',
            'cost_pct'      => 10.0,
            'is_active'     => false
        ]);
    }

    /** @test */
    public function cannot_update_a_priority_without_required_fields()
    {
        $user = User::factory()->create();
        $user->assignRole(['base', 'admin']);
        Sanctum::actingAs($user);

        $priority = Priority::factory()->create();

        $this->json('put', 'api/admin/priorities/' . $priority->id, [
            'name'          => null,
            'label'         => null,
            'sublabel'      => 'new sublabel',
            'description'   => 'new description',
            'cost_pct'      => 10,
        ])->assertStatus(422)
            ->assertJsonFragment(['The name field is required.'])
            ->assertJsonFragment(['The label field is required.']);
    }

    /** @test */
    public function cannot_update_a_priority_if_cost_pct_is_not_numeric()
    {
        $user = User::factory()->create();
        $user->assignRole(['base', 'admin']);
        Sanctum::actingAs($user);

        $priority = Priority::factory()->create();

        $this->json('put', 'api/admin/priorities/' . $priority->id, [
            'name'          => 'new name',
            'label'         => 'new label',
            'sublabel'      => 'new sublabel',
            'description'   => 'new description',
            'cost_pct'      => 'string',
        ])->assertStatus(422)
            ->assertJsonFragment(['The cost pct must be a number.']);
    }

    /** @test */
    public function non_authenticated_user_cannot_deleted_a_priority()
    {
        $this->json('delete', 'api/admin/priorities/100')
            ->assertUnauthorized();
    }

    /** @test */
    public function cannot_delete_non_existing_priority()
    {
        $user = User::factory()->create();
        $user->assignRole(['base', 'user', 'super_admin', 'compliance', 'agent']);
        Sanctum::actingAs($user);

        $this->json('delete', 'api/admin/priorities/100')
            ->assertNotFound();
    }

    /** @test */
    public function user_with_no_admin_role_cannot_delete_a_priority()
    {
        $user = User::factory()->create();
        $user->assignRole(['base', 'user', 'super_admin', 'compliance', 'agent']);
        Sanctum::actingAs($user);

        $priority = Priority::factory()->create();

        $this->json('delete', 'api/admin/priorities/' . $priority->id)
            ->assertForbidden();
    }

    /** @test */
    public function admin_user_can_delete_a_priority()
    {
        $user = User::factory()->create();
        $user->assignRole(['base', 'admin']);
        Sanctum::actingAs($user);

        $priority = Priority::factory()->create();

        $this->json('delete', 'api/admin/priorities/' . $priority->id)
            ->assertOk();

        $this->assertDatabaseMissing('priorities', [
            'id'    => $priority->id
        ]);
    }

    /** @test */
    public function non_authenticated_user_cannot_view_admin_index_of_priorities()
    {
        $this->json('get', 'api/admin/priorities/')
            ->assertUnauthorized();
    }

    /** @test */
    public function non_admin_user_cannot_view_admin_index_of_all_priorities()
    {
        $user = User::factory()->create();
        $user->assignRole(['base', 'user', 'compliance', 'super_admin', 'agent']);
        Sanctum::actingAs($user);

        $this->json('get', 'api/admin/priorities/')
            ->assertForbidden();
    }

    /** @test */
    public function admin_user_can_view_admin_index_of_all_priorities()
    {
        $user = User::factory()->create();
        $user->assignRole(['base', 'admin']);
        Sanctum::actingAs($user);

        Priority::factory()->count(10)->create();
        Priority::factory()->count(5)->create([
            'is_active' => false
        ]);

        $this->json('get', 'api/admin/priorities')
            ->assertOk()
            ->assertJsonCount(15, 'data')
            ->assertJsonStructure([
                'data' => [
                    [
                        'id',
                        'name',
                        'label',
                        'sublabel',
                        'description',
                        'cost_pct',
                        'is_active'
                    ]
                ]
            ]);
    }
}
