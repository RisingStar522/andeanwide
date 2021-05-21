<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Pair;
use App\Models\User;
use App\Models\Currency;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class PairTest extends TestCase
{
    use RefreshDatabase, DatabaseMigrations, WithFaker;

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
    public function anyone_can_view_index_of_pairs()
    {
        $baseCurrency = Currency::factory()->create();
        $quoteCurrency = Currency::factory()->create();
        Pair::factory()->count(10)->create([
            'base_id'   => $baseCurrency->id,
            'quote_id'  => $quoteCurrency->id
        ]);

        $this->json('get', 'api/pairs')
            ->assertOk()
            ->assertJsonCount(10, 'data')
            ->assertJsonStructure([
                'data' => [
                    [
                        'id',
                        'is_active',
                        'is_default',
                        'default_amount',
                        'min_amount',
                        'name',
                        'api_class',
                        'observation',
                        'base' => [
                            'id',
                            'name',
                            'symbol',
                            'can_send',
                            'can_receive',
                            'country' => [
                                'id',
                                'name',
                                'abbr'
                            ]
                        ],
                        'quote' => [
                            'id',
                            'name',
                            'symbol',
                            'can_send',
                            'can_receive',
                            'country' => [
                                'id',
                                'name',
                                'abbr'
                            ]
                        ],
                        'offset_by',
                        'offset',
                        'offset_to_corps',
                        'offset_to_imports',
                        'min_pip_value',
                        'show_inverse',
                        'max_tier_1',
                        'max_tier_2',
                        'more_rate',
                        'is_more_enabled',
                    ]
                ]
            ]);
    }

    /** @test */
    public function anyone_cannot_view_inactive_pairs()
    {
        Pair::factory()->count(10)->create([
            'is_active' => false
        ]);
        Pair::factory()->count(10)->create();

        $this->json('get', 'api/pairs')
            ->assertOk()
            ->assertJsonCount(10, 'data');
    }

    /** @test */
    public function non_authenticated_users_cannot_view_all_pairs()
    {
        $this->json('get', 'api/admin/pairs')
            ->assertUnauthorized();
    }

    /** @test */
    public function user_with_non_admin_role_cannot_view_all_pairs()
    {
        $user = User::factory()->create();
        $user->assignRole(['base', 'agent', 'super_admin', 'compliance']);
        Sanctum::actingAs($user);

        $this->json('get', 'api/admin/pairs')
            ->assertForbidden();
    }

    /** @test */
    public function user_with_admin_role_can_view_all_pairs()
    {
        $user = User::factory()->create();
        $user->assignRole(['base', 'admin']);
        Sanctum::actingAs($user);

        Pair::factory()->count(5)->create([
            'is_active' => false,
        ]);
        Pair::factory()->count(5)->create();

        $this->json('get', 'api/admin/pairs')
            ->assertOk()
            ->assertJsonCount(10, 'data')
            ->assertJsonStructure([
                'data' => [
                    [
                        'id',
                        'is_active',
                        'is_default',
                        'default_amount',
                        'min_amount',
                        'name',
                        'api_class',
                        'observation',
                        'base' => [],
                        'quote' => [],
                        'offset_by',
                        'offset',
                        'offset_to_corps',
                        'offset_to_imports',
                        'min_pip_value',
                        'show_inverse',
                        'max_tier_1',
                        'max_tier_2',
                        'more_rate',
                        'is_more_enabled',
                    ]
                ],
                'links' => [],
                'meta' => []
            ]);
    }

    /** @test */
    public function non_authenticated_user_cannot_view_a_single_pair()
    {
        $this->json('get', 'api/admin/pairs/100')
            ->assertUnauthorized();
    }

    /** @test */
    public function cannot_view_a_non_existing_pair()
    {
        $user = User::factory()->create();
        $user->assignRole(['base', 'agent', 'super_admin', 'compliance']);
        Sanctum::actingAs($user);

        $this->json('get', 'api/admin/pairs/100')
            ->assertNotFound();
    }

    /** @test */
    public function an_user_with_no_admin_role_cannot_view_a_single_pair()
    {
        $user = User::factory()->create();
        $user->assignRole(['base', 'agent', 'super_admin', 'compliance']);
        Sanctum::actingAs($user);

        $pair = Pair::factory()->create();

        $this->json('get', 'api/admin/pairs/' . $pair->id)
            ->assertForbidden();
    }

    /** @test */
    public function an_admin_user_can_view_a_single_pair()
    {
        $user = User::factory()->create();
        $user->assignRole(['base', 'admin']);
        Sanctum::actingAs($user);

        $pair = Pair::factory()->create();

        $this->json('get', 'api/admin/pairs/' . $pair->id)
            ->assertOK()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'is_active',
                    'is_default',
                    'default_amount',
                    'min_amount',
                    'name',
                    'api_class',
                    'observation',
                    'base' => [],
                    'quote' => [],
                    'offset_by',
                    'offset',
                    'offset_to_corps',
                    'offset_to_imports',
                    'min_pip_value',
                    'show_inverse',
                    'max_tier_1',
                    'max_tier_2',
                    'more_rate',
                    'is_more_enabled',
                ]
            ]);
    }

    /** @test */
    public function non_authenticated_user_cannot_create_a_pair()
    {
        $this->json('post', 'api/admin/pairs')
            ->assertUnauthorized();
    }

    /** @test */
    public function user_with_no_admin_role_cannot_create_a_pair()
    {
        $user = User::factory()->create();
        $user->assignRole(['base', 'agent', 'super_admin', 'compliance']);
        Sanctum::actingAs($user);

        $this->json('post', 'api/admin/pairs/')
            ->assertForbidden();
    }

    /** @test */
    public function an_admin_role_can_create_a_single_pair()
    {
        $user = User::factory()->create();
        $user->assignRole(['base', 'admin']);
        Sanctum::actingAs($user);

        $baseCurrency = Currency::factory()->create();
        $quoteCurrency = Currency::factory()->create();
        $pair = Pair::factory()->raw([
            'base_id'   => $baseCurrency->id,
            'quote_id'  => $quoteCurrency->id,
        ]);

        $this->json('post', 'api/admin/pairs', $pair)
            ->assertCreated();

        $this->assertDatabaseHas('pairs', [
            'name'              => $pair['name'],
            'observation'       => $pair['observation'],
            'base_id'           => $baseCurrency->id,
            'quote_id'          => $quoteCurrency->id,
            'default_amount'    => $pair['default_amount'],
            'min_amount'        => $pair['min_amount'],
            'api_class'         => $pair['api_class'],
            'offset_by'         => $pair['offset_by'],
            'offset'            => $pair['offset'],
            'offset_to_corps'   => $pair['offset_to_corps'],
            'offset_to_imports' => $pair['offset_to_imports'],
            'min_pip_value'     => $pair['min_pip_value'],
            'show_inverse'      => $pair['show_inverse'],
            'max_tier_1'        => $pair['max_tier_1'],
            'max_tier_2'        => $pair['max_tier_2'],
        ]);
    }

    /** @test */
    public function cannot_create_a_pair_without_required_fields()
    {
        $user = User::factory()->create();
        $user->assignRole(['base', 'admin']);
        Sanctum::actingAs($user);

        $pair = Pair::factory()->raw([
            'name'      => null,
            'base_id'   => null,
            'quote_id'  => null,
        ]);

        $this->json('post', 'api/admin/pairs', $pair)
            ->assertStatus(422)
            ->assertJsonFragment(['The name field is required.'])
            ->assertJsonFragment(['The base id field is required.'])
            ->assertJsonFragment(['The quote id field is required.']);
    }

    /** @test */
    public function name_field_must_be_unique()
    {
        $user = User::factory()->create();
        $user->assignRole(['base', 'admin']);
        Sanctum::actingAs($user);

        $pairCreated = Pair::factory()->create();
        $baseCurrency = Currency::factory()->create();
        $quoteCurrency = Currency::factory()->create();
        $pair = Pair::factory()->raw([
            'name'      => $pairCreated->name,
            'base_id'   => $baseCurrency->id,
            'quote_id'  => $quoteCurrency->id,
        ]);

        $this->json('post', 'api/admin/pairs', $pair)
            ->assertStatus(422)
            ->assertJsonFragment(['The name has already been taken.']);
    }

    /** @test */
    public function base_id_and_quote_id_must_exists()
    {
        $user = User::factory()->create();
        $user->assignRole(['base', 'admin']);
        Sanctum::actingAs($user);

        $pair = Pair::factory()->raw([
            'base_id'           => 100,
            'quote_id'          => 200,
        ]);

        $this->json('post', 'api/admin/pairs', $pair)
            ->assertStatus(422)
            ->assertJsonFragment(['The selected base id is invalid.'])
            ->assertJsonFragment(['The selected quote id is invalid.']);
    }

    /** @test */
    public function validate_numeric_values_with_to_create_new_pair()
    {
        $user = User::factory()->create();
        $user->assignRole(['base', 'admin']);
        Sanctum::actingAs($user);

        $baseCurrency = Currency::factory()->create();
        $quoteCurrency = Currency::factory()->create();
        $pair = Pair::factory()->raw([
            'base_id'           => $baseCurrency->id,
            'quote_id'          => $quoteCurrency->id,
            'default_amount'    => 'string',
            'min_amount'        => 'string',
            'offset'            => 'string',
            'min_pip_value'     => 'string',
            'max_tier_2'        => 'string',
            'max_tier_1'        => 'string',
            'more_rate'         => 'string',
        ]);

        $this->json('post', 'api/admin/pairs', $pair)
            ->assertStatus(422)
            ->assertJsonFragment(['The default amount must be a number.'])
            ->assertJsonFragment(['The min amount must be a number.'])
            ->assertJsonFragment(['The offset must be a number.'])
            ->assertJsonFragment(['The min pip value must be a number.'])
            ->assertJsonFragment(['The max tier 1 must be a number.'])
            ->assertJsonFragment(['The max tier 2 must be a number.'])
            ->assertJsonFragment(['The more rate must be a number.']);
    }

    /** @test */
    public function offset_to_imports_must_be_point_or_percentage_to_create_a_pair()
    {
        $user = User::factory()->create();
        $user->assignRole(['base', 'admin']);
        Sanctum::actingAs($user);

        $baseCurrency = Currency::factory()->create();
        $quoteCurrency = Currency::factory()->create();
        $pair = Pair::factory()->raw([
            'base_id'           => $baseCurrency->id,
            'quote_id'          => $quoteCurrency->id,
            'offset_by'         => 'string',
        ]);

        $this->json('post', 'api/admin/pairs', $pair)
            ->assertStatus(422)
            ->assertJsonFragment(['The selected offset by is invalid.']);
    }

    /** @test */
    public function non_authenticated_user_cannot_update_a_pair()
    {
        $this->json('put', 'api/admin/pairs/100')
            ->assertUnauthorized();
    }

    /** @test */
    public function cannot_update_non_existing_pair()
    {
        $user = User::factory()->create();
        $user->assignRole(['base', 'admin']);
        Sanctum::actingAs($user);

        $this->json('put', 'api/admin/pairs/100')
            ->assertNotFound();
    }

    /** @test */
    public function an_authenticated_user_with_no_admin_role_cannot_updated_a_pair()
    {
        $user = User::factory()->create();
        $user->assignRole(['base', 'super_admin', 'compliance', 'user', 'agent']);
        Sanctum::actingAs($user);

        $pair = Pair::factory()->create();
        $this->json('put', 'api/admin/pairs/' . $pair->id)
            ->assertForbidden();
    }

    /** @test */
    public function an_admin_user_can_create_a_pair()
    {
        $user = User::factory()->create();
        $user->assignRole(['base', 'admin']);
        Sanctum::actingAs($user);

        $pair = Pair::factory()->create();
        $baseCurrency = Currency::factory()->create();
        $quoteCurrency = Currency::factory()->create();
        $this->json('put', 'api/admin/pairs/' . $pair->id, [
            'name'              => 'new name',
            'is_active'         => false,
            'is_default'        => true,
            'api_class'         => 'new_api_class',
            'observation'       => 'new observation',
            'default_amount'    => 200000,
            'min_amount'        => 5000,
            'base_id'           => $baseCurrency->id,
            'quote_id'          => $quoteCurrency->id,
            'offset'            => 30,
            'offset_to_corps'   => 20,
            'offset_to_imports' => 10,
            'offset_by'         => 'point',
            'min_pip_value'     => 0.001,
            'show_inverse'      => true,
            'max_tier_1'        => 500000,
            'max_tier_2'        => 5000000,
            'more_rate'         => 500,
            'is_more_enabled'   => false,
        ])->assertOk();

        $this->assertDatabaseHas('pairs', [
            'name'              => 'new name',
            'is_active'         => false,
            'is_default'        => true,
            'api_class'         => 'new_api_class',
            'observation'       => 'new observation',
            'default_amount'    => 200000,
            'min_amount'        => 5000,
            'base_id'           => $baseCurrency->id,
            'quote_id'          => $quoteCurrency->id,
            'offset'            => 30,
            'offset_to_corps'   => 20,
            'offset_to_imports' => 10,
            'offset_by'         => 'point',
            'min_pip_value'     => 0.001,
            'show_inverse'      => true,
            'max_tier_1'        => 500000,
            'max_tier_2'        => 5000000,
            'more_rate'         => 500,
            'is_more_enabled'   => false,
        ]);
    }

    /** @test */
    public function cannot_update_without_required_fields()
    {
        $user = User::factory()->create();
        $user->assignRole(['base', 'admin']);
        Sanctum::actingAs($user);

        $pair = Pair::factory()->create();
        $this->json('put', 'api/admin/pairs/' . $pair->id, [
            'name'              => null,
            'is_active'         => false,
            'is_default'        => true,
            'api_class'         => 'new_api_class',
            'observation'       => 'new observation',
            'default_amount'    => 200000,
            'min_amount'        => 5000,
            'base_id'           => null,
            'quote_id'          => null,
            'offset'            => 10,
            'offset_by'         => 'point',
            'min_pip_value'     => 0.001,
            'show_inverse'      => true,
            'max_tier_1'        => 500000,
            'max_tier_2'        => 5000000,
            'more_rate'         => 500,
            'is_more_enabled'   => false,
        ])->assertStatus(422)
            ->assertJsonFragment(['The name field is required.'])
            ->assertJsonFragment(['The base id field is required.'])
            ->assertJsonFragment(['The quote id field is required.']);
    }

    /** @test */
    public function name_field_must_be_unique_to_update_a_pair()
    {
        $user = User::factory()->create();
        $user->assignRole(['base', 'admin']);
        Sanctum::actingAs($user);

        $pairCreated = Pair::factory()->create();
        $pair = Pair::factory()->create();
        $baseCurrency = Currency::factory()->create();
        $quoteCurrency = Currency::factory()->create();
        $this->json('put', 'api/admin/pairs/' . $pair->id, [
            'name'              => $pairCreated->name,
            'is_active'         => false,
            'is_default'        => true,
            'api_class'         => 'new_api_class',
            'observation'       => 'new observation',
            'default_amount'    => 200000,
            'min_amount'        => 5000,
            'base_id'           => $baseCurrency->id,
            'quote_id'          => $quoteCurrency->id,
            'offset'            => 10,
            'offset_by'         => 'point',
            'min_pip_value'     => 0.001,
            'show_inverse'      => true,
            'max_tier_1'        => 500000,
            'max_tier_2'        => 5000000,
            'more_rate'         => 500,
            'is_more_enabled'   => false,
        ])->assertStatus(422)
            ->assertJsonFragment(['The name has already been taken.']);
    }

    /** @test */
    public function base_id_and_quote_id_must_exist_to_update_a_pair()
    {

        $user = User::factory()->create();
        $user->assignRole(['base', 'admin']);
        Sanctum::actingAs($user);

        $pair = Pair::factory()->create();
        $this->json('put', 'api/admin/pairs/' . $pair->id, [
            'name'              => 'new name',
            'is_active'         => false,
            'is_default'        => true,
            'api_class'         => 'new_api_class',
            'observation'       => 'new observation',
            'default_amount'    => 200000,
            'min_amount'        => 5000,
            'base_id'           => 200,
            'quote_id'          => 400,
            'offset'            => 10,
            'offset_by'         => 'point',
            'min_pip_value'     => 0.001,
            'show_inverse'      => true,
            'max_tier_1'        => 500000,
            'max_tier_2'        => 5000000,
            'more_rate'         => 500,
            'is_more_enabled'   => false,
        ])->assertStatus(422)
            ->assertJsonFragment(['The selected base id is invalid.'])
            ->assertJsonFragment(['The selected quote id is invalid.']);
    }

    /** @test */
    public function cannot_update_a_pair_without_pass_the_numeric_validations()
    {
        $user = User::factory()->create();
        $user->assignRole(['base', 'admin']);
        Sanctum::actingAs($user);

        $pair = Pair::factory()->create();
        $baseCurrency = Currency::factory()->create();
        $quoteCurrency = Currency::factory()->create();
        $this->json('put', 'api/admin/pairs/' . $pair->id, [
            'name'              => 'new name',
            'is_active'         => false,
            'is_default'        => true,
            'api_class'         => 'new_api_class',
            'observation'       => 'new observation',
            'default_amount'    => 'string',
            'min_amount'        => 'string',
            'base_id'           => $baseCurrency->id,
            'quote_id'          => $quoteCurrency->id,
            'offset'            => 'string',
            'offset_by'         => 'point',
            'min_pip_value'     => 'string',
            'show_inverse'      => true,
            'max_tier_1'        => 'string',
            'max_tier_2'        => 'string',
            'more_rate'         => 'string',
            'is_more_enabled'   => false,
        ])->assertStatus(422)
            ->assertJsonFragment(['The default amount must be a number.'])
            ->assertJsonFragment(['The min amount must be a number.'])
            ->assertJsonFragment(['The offset must be a number.'])
            ->assertJsonFragment(['The min pip value must be a number.'])
            ->assertJsonFragment(['The max tier 1 must be a number.'])
            ->assertJsonFragment(['The max tier 2 must be a number.'])
            ->assertJsonFragment(['The more rate must be a number.']);
    }

    /** @test */
    public function offset_by_must_be_point_or_percentage_to_update_a_pair()
    {
        $user = User::factory()->create();
        $user->assignRole(['base', 'admin']);
        Sanctum::actingAs($user);

        $pair = Pair::factory()->create();
        $baseCurrency = Currency::factory()->create();
        $quoteCurrency = Currency::factory()->create();
        $this->json('put', 'api/admin/pairs/' . $pair->id, [
            'name'              => 'new name',
            'is_active'         => false,
            'is_default'        => true,
            'api_class'         => 'new_api_class',
            'observation'       => 'new observation',
            'default_amount'    => 200000,
            'min_amount'        => 5000,
            'base_id'           => $baseCurrency->id,
            'quote_id'          => $quoteCurrency->id,
            'offset'            => 10,
            'offset_by'         => 'string',
            'min_pip_value'     => 0.001,
            'show_inverse'      => true,
            'max_tier_1'        => 500000,
            'max_tier_2'        => 5000000,
            'more_rate'         => 500,
            'is_more_enabled'   => false,
        ])->assertStatus(422)
            ->assertJsonFragment(['The selected offset by is invalid.']);
    }

    /** @test */
    public function non_authenticated_user_cannot_delete_a_pair()
    {
        $this->json('delete', 'api/admin/pairs/100')
            ->assertUnauthorized();
    }

    /** @test */
    public function cannot_delete_a_non_existing_pair()
    {
        $user = User::factory()->create();
        $user->assignRole(['base', 'admin']);
        Sanctum::actingAs($user);

        $this->json('delete', 'api/admin/pairs/100')
            ->assertNotFound();
    }

    /** @test */
    public function an_authenticated_user_with_no_admin_role_cannot_delete_a_pair()
    {
        $user = User::factory()->create();
        $user->assignRole(['base', 'user', 'agent', 'super_admin', 'compliance']);
        Sanctum::actingAs($user);

        $pair = Pair::factory()->create();
        $this->json('delete', 'api/admin/pairs/' . $pair->id)
            ->assertForbidden();
    }

    /** @test */
    public function an_admin_user_can_delete_a_pair()
    {
        $user = User::factory()->create();
        $user->assignRole(['base', 'admin']);
        Sanctum::actingAs($user);

        $pair = Pair::factory()->create();
        $this->json('delete', 'api/admin/pairs/' . $pair->id)
            ->assertOk();

        $this->assertDatabaseMissing('pairs', [
            'id' => $pair->id,
        ]);
    }
}
