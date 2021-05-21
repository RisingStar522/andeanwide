<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Param;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class ParamTest extends TestCase
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
    public function anyone_can_view_index_of_params_on_generic_params()
    {
        Param::factory()->count(10)->create();

        $this->json('get', 'api/params')
            ->assertOk()
            ->assertJsonCount(10, 'data')
            ->assertJsonStructure([
                'data' => [
                    [
                        'id',
                        'name',
                        'label',
                        'description',
                        'value',
                        'value_type',
                        'default_value',
                        'created_at',
                        'updated_at'
                    ]
                ]
            ]);
    }

    /** @test */
    public function non_authenticated_user_cannot_view_index_of_params()
    {
        $this->json('get', 'api/admin/params')
            ->assertUnauthorized();
    }

    /** @test */
    public function non_super_admin_user_cannot_view_params()
    {
        $user = User::factory()->create();
        $user->assignRole(['base', 'admin', 'user', 'compliance', 'agent']);
        Sanctum::actingAs($user);

        $this->json('get', 'api/admin/params')
            ->assertForbidden();
    }

    /** @test */
    public function super_admin_user_can_view_index_of_params()
    {
        $user = User::factory()->create();
        $user->assignRole(['base', 'super_admin']);
        Sanctum::actingAs($user);

        Param::factory()->count(10)->create();

        $this->json('get', 'api/admin/params')
            ->assertOk()
            ->assertJsonCount(10, 'data')
            ->assertJsonStructure([
                'data' => [
                    [
                        'id',
                        'name',
                        'label',
                        'description',
                        'value',
                        'value_type',
                        'default_value',
                        'created_at',
                        'updated_at'
                    ]
                ]
            ]);
    }

    /** @test */
    public function non_authenticated_user_cannnot_view_a_single_param()
    {
        $this->json('get', 'api/admin/params/100')
            ->assertUnauthorized();
    }

    /** @test */
    public function cannot_view_non_existing_param()
    {
        $user = User::factory()->create();
        $user->assignRole(['base', 'admin', 'user', 'compliance', 'agent']);
        Sanctum::actingAs($user);

        $this->json('get', 'api/admin/params/100')
            ->assertNotFound();
    }

    /** @test */
    public function non_super_admin_user_cannot_view_a_single_param()
    {
        $user = User::factory()->create();
        $user->assignRole(['base', 'admin', 'user', 'compliance', 'agent']);
        Sanctum::actingAs($user);

        $param = Param::factory()->create();

        $this->json('get', 'api/admin/params/' . $param->id)
            ->assertForbidden();
    }

    /** @test */
    public function a_super_admin_user_can_view_a_single_param()
    {
        $user = User::factory()->create();
        $user->assignRole(['base', 'super_admin']);
        Sanctum::actingAs($user);

        $param = Param::factory()->create();

        $this->json('get', 'api/admin/params/' . $param->id)
            ->assertOk()
            ->assertJsonFragment([
                'data' => [
                    'id'            => $param->id,
                    'name'          => $param->name,
                    'label'         => $param->label,
                    'description'   => $param->description,
                    'value'         => $param->value,
                    'value_type'    => $param->value_type,
                    'default_value' => $param->default_value,
                    'created_at'    => $param->created_at,
                    'updated_at'    => $param->updated_at,
                ]
            ]);
    }

    /** @test */
    public function non_authenticated_user_cannot_create_a_param()
    {
        $this->json('post', 'api/admin/params')
            ->assertUnauthorized();
    }

    /** @test */
    public function non_super_admin_user_cannot_create_a_param()
    {
        $user = User::factory()->create();
        $user->assignRole(['base', 'admin', 'user', 'compliance', 'agent']);
        Sanctum::actingAs($user);

        $param = Param::factory()->create();

        $this->json('post', 'api/admin/params')
            ->assertForbidden();
    }

    /** @test */
    public function super_admin_user_can_create_a_param()
    {
        $user = User::factory()->create();
        $user->assignRole(['base', 'super_admin']);
        Sanctum::actingAs($user);

        $param = Param::factory()->raw();

        $this->json('post', 'api/admin/params/', $param)
            ->assertCreated();

        $this->assertDatabaseHas('params', [
            'name'          => $param['name'],
            'label'         => $param['label'],
            'description'   => $param['description'],
            'value'         => $param['value'],
            'value_type'    => $param['value_type'],
            'default_value' => $param['default_value'],
        ]);
    }

    /** @test */
    public function cannot_create_a_param_without_required_fields()
    {
        $user = User::factory()->create();
        $user->assignRole(['base', 'super_admin']);
        Sanctum::actingAs($user);

        $param = Param::factory()->raw([
            'name'  => null,
            'label' => null,
        ]);

        $this->json('post', 'api/admin/params/', $param)
            ->assertStatus(422)
            ->assertJsonFragment(['The name field is required.'])
            ->assertJsonFragment(['The label field is required.']);
    }

    /** @test */
    public function value_type_field_must_be__string_or_integer_or_decimal_or_array()
    {
        $user = User::factory()->create();
        $user->assignRole(['base', 'super_admin']);
        Sanctum::actingAs($user);

        $param = Param::factory()->raw([
            'value_type'    => 'null',
        ]);

        $this->json('post', 'api/admin/params/', $param)
            ->assertStatus(422)
            ->assertJsonFragment(['The selected value type is invalid.']);

        $param = Param::factory()->raw(['value_type' => 'string']);
        $this->json('post', 'api/admin/params/', $param)->assertCreated();

        $param = Param::factory()->raw(['value_type' => 'integer']);
        $this->json('post', 'api/admin/params/', $param)->assertCreated();

        $param = Param::factory()->raw(['value_type' => 'decimal']);
        $this->json('post', 'api/admin/params/', $param)->assertCreated();

        $param = Param::factory()->raw(['value_type' => 'array']);
        $this->json('post', 'api/admin/params/', $param)->assertCreated();

        $param = Param::factory()->raw(['value_type' => 'boolean']);
        $this->json('post', 'api/admin/params/', $param)->assertCreated();
    }

    /** @test */
    public function non_authenticated_user_cannot_update_a_param()
    {
        $this->json('put', 'api/admin/params/100')
            ->assertUnauthorized();
    }

    /** @test */
    public function cannot_update_a_non_existing_param()
    {
        $user = User::factory()->create();
        $user->assignRole(['base', 'admin', 'user', 'compliance', 'agent']);
        Sanctum::actingAs($user);

        $this->json('put', 'api/admin/params/100')
            ->assertNotFound();
    }

    /** @test */
    public function non_super_admin_user_cannot_update_a_param()
    {
        $user = User::factory()->create();
        $user->assignRole(['base', 'admin', 'user', 'compliance', 'agent']);
        Sanctum::actingAs($user);

        $param = Param::factory()->create();

        $this->json('put', 'api/admin/params/' . $param->id)
            ->assertForbidden();
    }

    /** @test */
    public function a_super_admin_can_update_a_param()
    {
        $user = User::factory()->create();
        $user->assignRole(['base', 'super_admin']);
        Sanctum::actingAs($user);

        $param = Param::factory()->create();

        $this->json('put', 'api/admin/params/' . $param->id, [
            'name'          => 'new name',
            'label'         => 'new label',
            'description'   => 'new description',
            'value'         => '100',
            'value_type'    => 'string',
            'default_value' => '0',
        ])->assertOk();

        $this->assertDatabaseHas('params', [
            'id'            => $param->id,
            'name'          => 'new name',
            'label'         => 'new label',
            'description'   => 'new description',
            'value'         => '100',
            'value_type'    => 'string',
            'default_value' => '0',
        ]);
    }

    /** @test */
    public function non_authenticated_user_cannot_delete_a_param()
    {
        $this->json('delete', 'api/admin/params/100')
            ->assertUnauthorized();
    }

    /** @test */
    public function cannot_delete_non_existing_param()
    {
        $user = User::factory()->create();
        $user->assignRole(['base', 'admin', 'user', 'compliance', 'agent']);
        Sanctum::actingAs($user);

        $this->json('delete', 'api/admin/params/100')
            ->assertNotFound();
    }

    /** @test */
    public function non_super_admin_user_cannot_delete_a_param()
    {
        $user = User::factory()->create();
        $user->assignRole(['base', 'admin', 'user', 'compliance', 'agent']);
        Sanctum::actingAs($user);

        $param = Param::factory()->create();

        $this->json('delete', 'api/admin/params/' . $param->id)
            ->assertForbidden();
    }

    /** @test */
    public function a_super_user_admin_can_delete_a_param()
    {
        $user = User::factory()->create();
        $user->assignRole(['base', 'super_admin']);
        Sanctum::actingAs($user);

        $param = Param::factory()->create();

        $this->json('delete', 'api/admin/params/' . $param->id)->assertOk();

        $this->assertDatabaseMissing('params', ['id' => $param->id]);
    }
}
