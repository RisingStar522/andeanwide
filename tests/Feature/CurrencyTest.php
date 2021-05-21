<?php

namespace Tests\Feature;

use App\Models\Country;
use App\Models\Currency;
use App\Models\User;
use Tests\TestCase;
use Spatie\Permission\Models\Role;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Sanctum\Sanctum;

class CurrencyTest extends TestCase
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
    public function anyone_can_fetch_all_currencies()
    {
        Currency::factory()->count(10)->create();
        $this->json('get', 'api/currencies')
            ->assertOk()
            ->assertJsonCount(10, 'data')
            ->assertJsonStructure([
                'data' => [
                    [
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
                    ]
                ]
            ]);
    }

    /** @test */
    public function anyone_can_view_only_active_currencies()
    {
        Currency::factory()->count(10)->create();
        Currency::factory()->count(10)->create([
            'is_active' => false
        ]);
        $this->json('get', 'api/currencies')
            ->assertOk()
            ->assertJsonCount(10, 'data')
            ->assertJsonStructure([
                'data' => [
                    [
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
                    ]
                ]
            ]);
    }

    /** @test */
    public function non_active_currencies_cannot_view_all_currencies()
    {
        $this->json('get', 'api/admin/currencies')
            ->assertUnauthorized();
    }

    /** @test */
    public function non_amin_role_usr_cannot_view_all_currencies()
    {
        $user = User::factory()->create();
        $user->assignRole(['base', 'agent', 'super_admin', 'compliance']);
        Sanctum::actingAs($user);

        $this->json('get', 'api/admin/currencies')
            ->assertForbidden();
    }

    /** @test */
    public function an_admin_user_can_view_all_()
    {
        $user = User::factory()->create();
        $user->assignRole(['base', 'admin']);
        Sanctum::actingAs($user);

        Currency::factory()->count(5)->create();
        Currency::factory()->count(5)->create([
            'is_active' => false
        ]);
        $this->json('get', 'api/admin/currencies')
            ->assertOk()
            ->assertJsonCount(10, 'data')
            ->assertJsonStructure([
                'data' => [
                    [
                        'id',
                        'name',
                        'symbol',
                        'can_send',
                        'can_receive',
                        'abbr',
                        'country' => [
                            'id',
                            'name',
                            'abbr'
                        ]
                    ]
                ],
                'meta' => [],
                'links' => []
            ]);
    }

    /** @test */
    public function non_authenticated_user_cannot_view_a_single_currency()
    {
        $this->json('get', 'api/admin/currencies/100')
            ->assertUnauthorized();
    }

    /** @test */
    public function user_with_non_admin_role_cannot_view_a_single_currency()
    {
        $user = User::factory()->create();
        $user->assignRole(['base', 'agent', 'super_admin', 'compliance']);
        Sanctum::actingAs($user);

        Currency::factory()->create();

        $this->json('get', 'api/admin/currencies/1')
            ->assertForbidden();
    }

    /** @test */
    public function an_admin_user_can_view_a_single_currency()
    {
        $user = User::factory()->create();
        $user->assignRole(['base', 'admin']);
        Sanctum::actingAs($user);

        Currency::factory()->create();

        $this->json('get', 'api/admin/currencies/1')
            ->assertOK()
            ->assertJsonStructure([
                'data' => [
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
                ]
            ]);
    }

    /** @test */
    public function non_authenticated_user_cannot_create_a_currency()
    {
        $this->json('post', 'api/admin/currencies')
            ->assertUnauthorized();
    }

    /** @test */
    public function non_user_with_admin_role_cannot_create_a_currency()
    {
        $user = User::factory()->create();
        $user->assignRole(['base', 'agent', 'super_admin', 'compliance']);
        Sanctum::actingAs($user);

        $currency = Currency::factory()->raw();

        $this->json('post', 'api/admin/currencies', $currency)
            ->assertForbidden();

        $this->assertDatabaseMissing('currencies', [
            'name' => $currency['name']
        ]);
    }

    /** @test */
    public function an_admin_user_can_create_a_currency()
    {
        $user = User::factory()->create();
        $user->assignRole(['base', 'admin']);
        Sanctum::actingAs($user);

        $country = Country::factory()->create();
        $currency = Currency::factory()->raw([
            'country_id' => $country
        ]);

        $this->json('post', 'api/admin/currencies', $currency)
            ->assertCreated();

        $this->assertDatabaseHas('currencies', [
            'name'          => $currency['name'],
            'symbol'        => $currency['symbol'],
            'country_id'    => $country->id
        ]);
    }

    /** @test */
    public function cannot_create_a_currency_without_required_fields()
    {
        $user = User::factory()->create();
        $user->assignRole(['base', 'admin']);
        Sanctum::actingAs($user);

        $currency = Currency::factory()->raw([
            'name'          => null,
            'symbol'        => null,
            'country_id'    => null,
        ]);

        $this->json('post', 'api/admin/currencies', $currency)
            ->assertStatus(422)
            ->assertJsonFragment(['The name field is required.'])
            ->assertJsonFragment(['The symbol field is required.'])
            ->assertJsonFragment(['The country id field is required.']);
    }

    /** @test */
    public function country_must_exist_when_create_a_currency()
    {
        $user = User::factory()->create();
        $user->assignRole(['base', 'admin']);
        Sanctum::actingAs($user);

        $currency = Currency::factory()->raw([
            'country_id'    => 100,
        ]);

        $this->json('post', 'api/admin/currencies', $currency)
            ->assertStatus(422)
            ->assertJsonFragment(['The selected country id is invalid.']);
    }

    /** @test */
    public function non_authenticated_user_cannot_update_a_currency()
    {
        $this->json('put', 'api/admin/currencies/1')
            ->assertUnauthorized();
    }

    /** @test */
    public function cannot_update_a_non_existing_currency()
    {
        $user = User::factory()->create();
        $user->assignRole(['base', 'agent', 'super_admin', 'compliance']);
        Sanctum::actingAs($user);

        $this->json('put', 'api/admin/currencies/100')
            ->assertNotFound();
    }

    /** @test */
    public function non_user_without_admin_role_cannot_update_a_currency()
    {
        $user = User::factory()->create();
        $user->assignRole(['base', 'agent', 'super_admin', 'compliance']);
        Sanctum::actingAs($user);

        $currency = Currency::factory()->create();

        $this->json('put', 'api/admin/currencies/' . $currency->id)
            ->assertForbidden();
    }

    /** @test */
    public function an_admin_user_can_update_a_currency()
    {
        $user = User::factory()->create();
        $user->assignRole(['base', 'admin']);
        Sanctum::actingAs($user);

        $country = Country::factory()->create();
        $currency = Currency::factory()->create();

        $this->json('put', 'api/admin/currencies/' . $currency->id, [
            'name'          => 'new name',
            'symbol'        => 'new symbol',
            'country_id'    => $country->id,
            'is_active'     => 0,
            'can_send'      => 0,
            'can_receive'   => 0
        ])->assertOk();

        $this->assertDatabaseHas('currencies', [
            'name'          => 'new name',
            'symbol'        => 'new symbol',
            'is_active'     => false,
            'can_send'      => false,
            'can_receive'   => false
        ]);
    }

    /** @test */
    public function can_update_a_currency_with_the_same_name_and_the_same_symbol()
    {
        $user = User::factory()->create();
        $user->assignRole(['base', 'admin']);
        Sanctum::actingAs($user);

        $country = Country::factory()->create();
        $currency = Currency::factory()->create();

        $this->json('put', 'api/admin/currencies/' . $currency->id, [
            'name'          => $currency->name,
            'symbol'        => $currency->symbol,
            'country_id'    => $country->id,
            'is_active'     => 0,
            'can_send'      => 0,
            'can_receive'   => 0
        ])->assertOk();

        $this->assertDatabaseHas('currencies', [
            'name'          => $currency->name,
            'symbol'        => $currency->symbol,
            'is_active'     => false,
            'can_send'      => false,
            'can_receive'   => false
        ]);
    }

    /** @test */
    public function cannot_update_a_currency_without_required_field()
    {
        $user = User::factory()->create();
        $user->assignRole(['base', 'admin']);
        Sanctum::actingAs($user);

        $currency = Currency::factory()->create();

        $this->json('put', 'api/admin/currencies/' . $currency->id, [
            'name'          => null,
            'symbol'        => null,
            'country_id'    => null,
            'is_active'     => 0,
            'can_send'      => 0,
            'can_receive'   => 0
        ])->assertStatus(422)
            ->assertJsonFragment(['The name field is required.'])
            ->assertJsonFragment(['The symbol field is required.'])
            ->assertJsonFragment(['The country id field is required.']);

    }

    /** @test */
    public function country_must_exist_to_update_an_currency()
    {
        $user = User::factory()->create();
        $user->assignRole(['base', 'admin']);
        Sanctum::actingAs($user);

        $currency = Currency::factory()->create();

        $this->json('put', 'api/admin/currencies/' . $currency->id, [
            'name'          => 'new name',
            'symbol'        => 'new symbol',
            'country_id'    => 100,
            'is_active'     => 0,
            'can_send'      => 0,
            'can_receive'   => 0
        ])->assertStatus(422)
            ->assertJsonFragment(['The selected country id is invalid.']);
    }

    /** @test */
    public function non_authenticated_user_cannot_delete_a_currency()
    {
        $this->json('delete', 'api/admin/currencies/100')
            ->assertUnauthorized();
    }

    /** @test */
    public function an_user_without_admin_role_cannot_delete_a_currency()
    {
        $user = User::factory()->create();
        $user->assignRole(['base', 'agent', 'super_admin', 'compliance']);
        Sanctum::actingAs($user);

        $currency = Currency::factory()->create();

        $this->json('delete', 'api/admin/currencies/' . $currency->id)
            ->assertForbidden();
    }

    /** @test */
    public function cannot_delete_non_existing_currency()
    {
        $user = User::factory()->create();
        $user->assignRole(['base', 'admin']);
        Sanctum::actingAs($user);

        $this->json('delete', 'api/admin/currencies/100')
            ->assertNotFound();
    }

    /** @test */
    public function an_admin_user_can_delete_a_currency()
    {
        $user = User::factory()->create();
        $user->assignRole(['base', 'admin']);
        Sanctum::actingAs($user);

        $currency = Currency::factory()->create();

        $this->json('delete', 'api/admin/currencies/' . $currency->id)
            ->assertOk();

        $this->assertDatabaseMissing('currencies', [
            'id' => $currency->id
        ]);
    }
}
