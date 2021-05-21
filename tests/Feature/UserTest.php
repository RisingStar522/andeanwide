<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class UserTest extends TestCase
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
    public function an_user_can_register_a_itself_with_user_role()
    {
        $user = User::factory()->raw();

        $this->json('post', 'api/users/register', [
            'name'                  => $user['name'],
            'email'                 => $user['email'],
            'password'              => 'abc1234$',
            'password_confirmation' => 'abc1234$',
        ])->assertCreated();

        $this->assertDatabaseHas('users', [
            'name'  => $user['name'],
            'email' => $user['email'],
        ]);

        $user = User::find(1);
        $this->assertTrue($user->hasRole('user'));
        $this->assertFalse($user->hasRole('base'));
        $this->assertFalse($user->hasRole('agent'));
        $this->assertFalse($user->hasRole('admin'));
        $this->assertFalse($user->hasRole('compliance'));
        $this->assertFalse($user->hasRole('super_admin'));
    }

    /** @test */
    public function a_super_admin_user_can_create_a_base_admin_user()
    {
        $su = User::factory()->create();
        $su->assignRole('super_admin');
        Sanctum::actingAs($su);

        $user = User::factory()->raw();
        $this->json('post', 'api/admin/users/register', [
            'name'                  => $user['name'],
            'email'                 => $user['email'],
            'password'              => 'abc1234$',
            'password_confirmation' => 'abc1234$',
        ])->assertCreated();

        $this->assertDatabaseHas('users', [
            'name'  => $user['name'],
            'email' => $user['email'],
        ]);

        $user = User::where('name', $user['name'])->firstOrFail();
        $this->assertTrue($user->hasRole('base'));
        $this->assertFalse($user->hasRole('agent'));
        $this->assertFalse($user->hasRole('user'));
        $this->assertFalse($user->hasRole('admin'));
        $this->assertFalse($user->hasRole('compliance'));
        $this->assertFalse($user->hasRole('super_admin'));
    }

    /** @test */
    public function non_authenticated_user_cannot_register_an_admin_user()
    {
        $this->json('post', 'api/admin/users/register')
            ->assertUnauthorized();
    }

    /** @test */
    public function no_super_admin_user_cannot_create_an_admin_user()
    {
        $su = User::factory()->create();
        $su->assignRole('user');
        $su->assignRole('agent');
        $su->assignRole('admin');
        $su->assignRole('compliance');
        $su->assignRole('base');
        Sanctum::actingAs($su);

        $this->json('post', 'api/admin/users/register')
            ->assertForbidden();
    }

    /** @test */
    public function non_authenticated_user_cannot_view_admin_users()
    {
        $this->json('get', 'api/admin/users')
            ->assertUnauthorized();
    }

    /** @test */
    public function non_super_admin_user_cannot_view_admin_users()
    {
        $su = User::factory()->create();
        $su->assignRole('user');
        $su->assignRole('agent');
        $su->assignRole('admin');
        $su->assignRole('compliance');
        $su->assignRole('base');
        Sanctum::actingAs($su);

        $this->json('get', 'api/admin/users')
            ->assertForbidden();
    }

    /** @test */
    public function super_admin_user_can_view_admin_users()
    {
        $su = User::factory()->create();
        $su->assignRole('super_admin');
        Sanctum::actingAs($su);

        /** 3 admin users */
        User::factory()->create()->assignRole('admin');
        User::factory()->create()->assignRole('compliance');
        User::factory()->create()->assignRole('base');

        /** one non admin user */
        User::factory()->create()->assignRole('user');
        User::factory()->create()->assignRole('agent');

        $this->json('get', 'api/admin/users')
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [
                    [
                        'id',
                        'name',
                        'email',
                        'roles' => [],
                        'created_at',
                        'updated_at',
                    ]
                ],
                'meta' => [],
                'links' => []
            ]);
    }

    /** @test */
    public function non_authenticated_user_view_a_single_admin_user()
    {
        $this->json('get', 'api/admin/users/1')
            ->assertUnauthorized();
    }

    /** @test */
    public function user_with_no_super_admin_role_cannot_view_a_single_admin_user()
    {
        $su = User::factory()->create();
        $su->assignRole(['base', 'user', 'agent', 'admin', 'compliance']);
        Sanctum::actingAs($su);

        $user =  User::factory()->create();
        $user->assignRole('admin');

        $this->json('get', 'api/admin/users/' . $user->id)
            ->assertForbidden();
    }

    /** @test */
    public function user_with_super_admin_role_can_view_a_single_admin_user()
    {
        $su = User::factory()->create();
        $su->assignRole(['super_admin']);
        Sanctum::actingAs($su);

        $user =  User::factory()->create();
        $user->assignRole('base');

        $this->json('get', 'api/admin/users/' . $user->id)
            ->assertOk()
            ->assertJsonFragment([
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
            ])
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'email',
                    'roles' => [],
                    'created_at',
                    'updated_at'
                ]
            ]);
    }

    /** @test */
    public function cannot_view_non_existing_user()
    {
        $su = User::factory()->create();
        $su->assignRole(['super_admin']);
        Sanctum::actingAs($su);

        $this->json('get', 'api/admin/users/100')
            ->assertNotFound();
    }

    /** @test */
    public function non_authenticated_user_cannot_assign_role_to_admin_user()
    {
        $this->json('post', 'api/admin/users/1/add-role')
            ->assertUnauthorized();
    }

    /** @test */
    public function user_with_no_super_admin_cannot_assing_rol_to_admin_user()
    {
        $su = User::factory()->create();
        $su->assignRole(['base', 'user', 'agent', 'admin', 'compliance']);
        Sanctum::actingAs($su);

        $user = User::factory()->create();
        $user->assignRole('base');

        $this->json('post', 'api/admin/users/' . $user->id . '/add-role')
            ->assertForbidden();
    }

    /** @test */
    public function user_with_admin_can_assign_a_role_to_a_user()
    {
        $su = User::factory()->create();
        $su->assignRole(['super_admin']);
        Sanctum::actingAs($su);

        $user = User::factory()->create();
        $user->assignRole('base');

        $this->json('post', 'api/admin/users/' . $user->id . '/add-role', [
            'role'  => 'admin'
        ])->assertOk();

        $user->refresh();
        $this->assertTrue($user->hasRole('admin'));
    }

    /** @test */
    public function cannot_assign_role_to_an_user_with_user_role()
    {
        $su = User::factory()->create();
        $su->assignRole(['super_admin']);
        Sanctum::actingAs($su);

        $user = User::factory()->create();
        $user->assignRole('user');

        $this->json('post', 'api/admin/users/' . $user->id . '/add-role', [
            'role'  => 'admin'
        ])->assertForbidden();

        $user->refresh();
        $this->assertFalse($user->hasRole('admin'));
    }

    /** @test */
    public function role_field_is_required_to_assign_a_role()
    {
        $su = User::factory()->create();
        $su->assignRole(['super_admin']);
        Sanctum::actingAs($su);

        $user = User::factory()->create();
        $user->assignRole('base');

        $this->json('post', 'api/admin/users/' . $user->id . '/add-role')->assertStatus(422);
    }

    /** @test */
    public function only_can_assign_admin_or_super_admin_or_compliance()
    {
        $su = User::factory()->create();
        $su->assignRole(['super_admin']);
        Sanctum::actingAs($su);

        $user = User::factory()->create();
        $user->assignRole('base');

        $this->json('post', 'api/admin/users/' . $user->id . '/add-role', ['role'  => 'admin'])->assertOk();
        $this->json('post', 'api/admin/users/' . $user->id . '/add-role', ['role'  => 'super_admin'])->assertOk();
        $this->json('post', 'api/admin/users/' . $user->id . '/add-role', ['role'  => 'compliance'])->assertOk();
        $this->json('post', 'api/admin/users/' . $user->id . '/add-role', ['role'  => 'another'])->assertStatus(422);

        $user->refresh();
        $this->assertTrue($user->hasRole('admin'));
        $this->assertTrue($user->hasRole('super_admin'));
        $this->assertTrue($user->hasRole('compliance'));
    }

    /** @test */
    public function can_assign_agent_role_to_a_user_with_user_role_and_account_type_equal_to_corporative()
    {
        $su = User::factory()->create();
        $su->assignRole(['super_admin']);
        Sanctum::actingAs($su);

        $user = User::factory()->create([
            'account_type' => 'corporative'
        ]);
        $user->assignRole('user');

        $this->json('post', 'api/admin/users/' . $user->id . '/add-role', ['role'  => 'agent'])->assertOk();

        $user->refresh();
        $this->assertTrue($user->hasRole('agent'));
    }

    /** @test */
    public function cannot_assign_agent_role_to_non_corporative_user()
    {
        $su = User::factory()->create();
        $su->assignRole(['super_admin']);
        Sanctum::actingAs($su);

        $user = User::factory()->create();
        $user->assignRole('user');

        $this->json('post', 'api/admin/users/' . $user->id . '/add-role', ['role'  => 'agent'])->assertForbidden();

        $user->refresh();
        $this->assertFalse($user->hasRole('agent'));
    }

    /** @test */
    public function cannot_assign_agent_role_to_an_administrative_role()
    {
        $su = User::factory()->create();
        $su->assignRole(['super_admin']);
        Sanctum::actingAs($su);

        $user = User::factory()->create([
            'account_type' => 'corporative'
        ]);
        $user->assignRole('admin');
        $user->assignRole('super_admin');
        $user->assignRole('compliance');

        $this->json('post', 'api/admin/users/' . $user->id . '/add-role', ['role'  => 'agent'])->assertForbidden();

        $user->refresh();
        $this->assertFalse($user->hasRole('agent'));
    }

    /** @test */
    public function non_authenticated_user_cannot_remove_role()
    {
        $this->json('post', 'api/admin/users/1/remove-role')->assertUnauthorized();
    }

    /** @test */
    public function non_super_admin_user_cannot_remove_a_role_to_a_user()
    {
        $su = User::factory()->create();
        $su->assignRole(['base', 'user', 'agent', 'admin', 'compliance']);
        Sanctum::actingAs($su);

        $user = User::factory()->create();
        $user->assignRole('base');

        $this->json('post', 'api/admin/users/' . $user->id . '/remove-role', ['role'  => 'agent'])->assertForbidden();

    }

    /** @test */
    public function super_admin_user_can_remove_a_role_to_a_user()
    {
        $su = User::factory()->create();
        $su->assignRole(['super_admin']);
        Sanctum::actingAs($su);

        $user = User::factory()->create();
        $user->assignRole(['base', 'agent']);

        $this->json('post', 'api/admin/users/' . $user->id . '/remove-role', ['role'  => 'agent'])
            ->assertOk();

        $user->refresh();
        $this->assertFalse($user->hasRole('agent'));
        $this->assertTrue($user->hasRole('base'));
    }

    /** @test */
    public function role_field_in_request_is_required_in_remove_role()
    {
        $su = User::factory()->create();
        $su->assignRole(['super_admin']);
        Sanctum::actingAs($su);

        $user = User::factory()->create();
        $user->assignRole('base');

        $this->json('post', 'api/admin/users/' . $user->id . '/remove-role')->assertStatus(422);
    }

    /** @test */
    public function role_field_must_be_agent_or_admin_or_compliance()
    {
        $su = User::factory()->create();
        $su->assignRole(['super_admin']);
        Sanctum::actingAs($su);

        $user = User::factory()->create();
        $user->assignRole(['base', 'agent', 'admin', 'compliance']);

        $this->json('post', 'api/admin/users/' . $user->id . '/remove-role', ['role' => 'agent'])->assertOk();
        $this->json('post', 'api/admin/users/' . $user->id . '/remove-role', ['role' => 'admin'])->assertOk();
        $this->json('post', 'api/admin/users/' . $user->id . '/remove-role', ['role' => 'compliance'])->assertOk();
        $this->json('post', 'api/admin/users/' . $user->id . '/remove-role', ['role' => 'base'])->assertStatus(422);
        $this->json('post', 'api/admin/users/' . $user->id . '/remove-role', ['role' => 'another'])->assertStatus(422);

        $user->refresh();
        $this->assertTrue($user->hasRole('base'));
        $this->assertFalse($user->hasRole('admin'));
        $this->assertFalse($user->hasRole('agent'));
        $this->assertFalse($user->hasRole('compliance'));
    }

    /** @test */
    public function cannot_remove_role_to_non_existing_user()
    {
        $su = User::factory()->create();
        $su->assignRole(['super_admin']);
        Sanctum::actingAs($su);

        $this->json('post', 'api/admin/users/100/remove-role', ['role' => 'agent'])->assertNotFound();
    }

    /** @test */
    public function non_authenticated_user_cannot_view_index_of_users_with_user_role()
    {
        $this->json('get', 'api/admin/users/all')
            ->assertUnauthorized();
    }

    /** @test */
    public function user_without_admin_or_compliance_role_cannot_view_index_of_users_with_user_role()
    {
        $su = User::factory()->create();
        $su->assignRole(['user', 'base', 'super_admin', 'agent']);
        Sanctum::actingAs($su);

        $this->json('get', 'api/admin/users/all')
            ->assertForbidden();
    }

    /** @test */
    public function admin_user_can_view_index_of_users()
    {
        $su = User::factory()->create();
        $su->assignRole(['base', 'admin']);
        Sanctum::actingAs($su);

        $users = User::factory()->count(10)->create();
        foreach ($users as $user) {
            $user->assignRole('user');
        }

        $users = User::factory()->count(5)->create();
        foreach ($users as $user) {
            $user->assignRole(['base', 'admin']);
        }

        $this->json('get', 'api/admin/users/all')
            ->assertOk()
            ->assertJsonCount(10, 'data')
            ->assertJsonStructure([
                'data' => [
                    [
                        'id',
                        'name',
                        'email',
                        'roles' => [],
                        'created_at',
                        'updated_at',
                    ]
                ]
            ]);
    }

    /** @test */
    public function compliance_user_can_view_index_of_users()
    {
        $su = User::factory()->create();
        $su->assignRole(['base', 'compliance']);
        Sanctum::actingAs($su);

        $users = User::factory()->count(10)->create();
        foreach ($users as $user) {
            $user->assignRole('user');
        }

        $users = User::factory()->count(5)->create();
        foreach ($users as $user) {
            $user->assignRole(['base', 'admin']);
        }

        $this->json('get', 'api/admin/users/all')
            ->assertOk()
            ->assertJsonCount(10, 'data')
            ->assertJsonStructure([
                'data' => [
                    [
                        'id',
                        'name',
                        'email',
                        'roles' => [],
                        'created_at',
                        'updated_at',
                    ]
                ]
            ]);
    }
}
