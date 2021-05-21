<?php

namespace Tests\Feature;

use App\Models\Bank;
use App\Models\Country;
use Tests\TestCase;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class BankTest extends TestCase
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
    public function anyone_can_view_index_of_bank_by_country()
    {
        $country = Country::factory()->create();
        Bank::factory()->count(5)->create();
        Bank::factory()->count(10)->create([
            'country_id' => $country->id,
        ]);

        $this->json('get', 'api/countries/' . $country->id . '/banks')
            ->assertOk()
            ->assertJsonCount(10, 'data')
            ->assertJsonStructure([
                'data' => [
                    [
                        'id',
                        'country_id',
                        'name',
                        'abbr',
                        'code',
                        'created_at',
                        'updated_at'
                    ]
                ]
            ]);
    }

    /** @test */
    public function cannot_view_index_by_country_to_a_no_existing_country()
    {
        $this->json('get', 'api/countries/100/banks')
            ->assertNotFound();
    }

    /** @test */
    public function non_registered_user_cannot_view_a_list_of_banks()
    {
        $this->json('get', 'api/admin/banks')
            ->assertUnauthorized();
    }

    /** @test */
    public function anyone_cannot_view_a_list_of_banks_but_admin_can()
    {
        $user = User::factory()->create();
        $user->assignRole(['user', 'base', 'agent', 'compliance', 'super_admin']);
        Sanctum::actingAs($user);

        $banks = Bank::factory()->count(10)->create();

        $this->json('get', 'api/admin/banks')
            ->assertForbidden();
    }

    /** @test */
    public function admin_can_view_list_of_banks()
    {
        $user = User::factory()->create();
        $user->assignRole(['admin']);
        Sanctum::actingAs($user);

        $banks = Bank::factory()->count(10)->create();

        $this->json('get', 'api/admin/banks')
            ->assertOk()
            ->assertJsonCount(10, 'data')
            ->assertJsonStructure([
                'data' => [
                    [
                        'id',
                        'country_id',
                        'name',
                        'abbr',
                        'code',
                        'created_at',
                        'updated_at'
                    ]
                ],
                'meta' => [],
                'links' => []
            ]);
    }

    /** @test */
    public function non_authenticated_user_cannot_view_a_single_bank()
    {
        $this->json('get', 'api/admin/banks/100')
            ->assertUnauthorized();
    }

    /** @test */
    public function cannot_view_non_existing_bank()
    {
        $user = User::factory()->create();
        $user->assignRole(['user', 'base', 'agent', 'compliance', 'super_admin']);
        Sanctum::actingAs($user);

        $this->json('get', 'api/admin/banks/100')
            ->assertNotFound();
    }

    /** @test */
    public function non_admin_user_cannot_view_a_single_bank()
    {
        $user = User::factory()->create();
        $user->assignRole(['user', 'base', 'agent', 'compliance', 'super_admin']);
        Sanctum::actingAs($user);

        $banks = Bank::factory()->create();

        $this->json('get', 'api/admin/banks/' . $banks->id)
            ->assertForbidden();
    }

    /** @test */
    public function admin_user_can_view_a_single_bank()
    {
        $user = User::factory()->create();
        $user->assignRole(['admin']);
        Sanctum::actingAs($user);

        $bank = Bank::factory()->create();

        $this->json('get', 'api/admin/banks/' . $bank->id)
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'country_id',
                    'name',
                    'abbr',
                    'code',
                    'created_at',
                    'updated_at'
                ]
            ]);
    }

    /** @test */
    public function non_autehnticated_user_cannot_create_a_bank()
    {
        $this->json('post', 'api/admin/banks')
            ->assertUnauthorized();
    }

    /** @test */
    public function non_admin_user_cannot_create_a_bank()
    {
        $user = User::factory()->create();
        $user->assignRole(['user', 'base', 'agent', 'compliance', 'super_admin']);
        Sanctum::actingAs($user);

        $this->json('post', 'api/admin/banks')
            ->assertForbidden();
    }

    /** @test */
    public function admin_user_can_create_a_bank()
    {
        $user = User::factory()->create();
        $user->assignRole(['admin']);
        Sanctum::actingAs($user);

        $country = Country::factory()->create();
        $bank = Bank::factory()->raw([
            'country_id' => $country->id
        ]);

        $this->json('post', 'api/admin/banks', $bank)
            ->assertCreated();

        $this->assertDatabaseHas('banks', [
            'country_id'    => $country->id,
            'name'          => $bank['name'],
            'code'          => $bank['code'],
            'abbr'          => $bank['abbr'],
        ]);
    }

    /** @test */
    public function cannot_create_a_bank_without_required_fields()
    {
        $user = User::factory()->create();
        $user->assignRole(['admin']);
        Sanctum::actingAs($user);

        $bank = Bank::factory()->raw([
            'name'          => null,
            'country_id'    => null,
            'code'          => null
        ]);

        $this->json('post', 'api/admin/banks', $bank)
            ->assertStatus(422)
            ->assertJsonFragment(['The name field is required.'])
            ->assertJsonFragment(['The country id field is required.'])
            ->assertJsonFragment(['The code field is required.']);
    }

    /** @test */
    public function cannot_create_a_bank_if_the_country_does_not_exits()
    {
        $user = User::factory()->create();
        $user->assignRole(['admin']);
        Sanctum::actingAs($user);

        $bank = Bank::factory()->raw([
            'country_id'    => 100,
        ]);

        $this->json('post', 'api/admin/banks', $bank)
            ->assertStatus(422)
            ->assertJsonFragment(['The selected country id is invalid.']);
    }

    /** @test */
    public function non_authenticated_user_cannot_update_a_bank()
    {
        $this->json('put', 'api/admin/banks/100')
            ->assertUnauthorized();
    }

    /** @test */
    public function cannot_update_a_non_existing_bank()
    {
        $user = User::factory()->create();
        $user->assignRole(['user', 'base', 'agent', 'compliance', 'super_admin']);
        Sanctum::actingAs($user);

        $this->json('put', 'api/admin/banks/100')
            ->assertNotFound();
    }

    /** @test */
    public function non_admin_user_cannot_update_a_bank()
    {
        $user = User::factory()->create();
        $user->assignRole(['user', 'base', 'agent', 'compliance', 'super_admin']);
        Sanctum::actingAs($user);

        $bank = Bank::factory()->create();

        $this->json('put', 'api/admin/banks/' . $bank->id)
            ->assertForbidden();
    }

    /** @test */
    public function non_authenticated_user_cannot_activate_or_deactivate_a_bank()
    {
        $this->json('post', 'api/admin/banks/100/activate')
            ->assertUnauthorized();

        $this->json('post', 'api/admin/banks/100/deactivate')
            ->assertUnauthorized();
    }

    /** @test */
    public function cannot_activate_or_deactivate_a_non_existing_bank()
    {
        $user = User::factory()->create();
        $user->assignRole(['user', 'base', 'agent', 'compliance', 'super_admin']);
        Sanctum::actingAs($user);

        $this->json('post', 'api/admin/banks/100/activate')
            ->assertNotFound();

        $this->json('post', 'api/admin/banks/100/deactivate')
            ->assertNotFound();
    }

    /** @test */
    public function non_admin_user_cannot_activate_or_deactivate_a_bank()
    {
        $user = User::factory()->create();
        $user->assignRole(['user', 'base', 'agent', 'compliance', 'super_admin']);
        Sanctum::actingAs($user);

        $bank = Bank::factory()->create();

        $this->json('post', 'api/admin/banks/' . $bank->id . '/activate')
            ->assertForbidden();

        $this->json('post', 'api/admin/banks/' . $bank->id . '/deactivate')
            ->assertForbidden();
    }

    /** @test */
    public function admin_user_can_activate_or_deactivate_a_bank()
    {
        $user = User::factory()->create();
        $user->assignRole(['base', 'admin']);
        Sanctum::actingAs($user);

        $bank = Bank::factory()->create([
            'is_active' => true
        ]);

        $this->json('post', 'api/admin/banks/' . $bank->id . '/deactivate')
            ->assertOk();

        $this->assertDatabaseHas('banks', [
            'id'        => $bank->id,
            'is_active' => false
        ]);

        $this->json('post', 'api/admin/banks/' . $bank->id . '/activate')
            ->assertOk();

        $this->assertDatabaseHas('banks', [
            'id'        => $bank->id,
            'is_active' => true
        ]);
    }
}
