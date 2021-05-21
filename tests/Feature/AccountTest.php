<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Bank;
use App\Models\User;
use App\Models\Account;
use App\Models\Country;
use App\Models\Currency;
use Laravel\Sanctum\Sanctum;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class AccountTest extends TestCase
{
    use DatabaseMigrations, RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    /** @test */
    public function non_authenticated_user_cannot_view_index_of_accounts()
    {
        $this->json('get', '/api/accounts')
            ->assertUnauthorized();
    }

    /** @test */
    public function any_authenticated_user_can_view_the_index_of_accounts()
    {
        $user = User::factory()->create();
        $user->assignRole(['user', 'base', 'admin', 'super_admin', 'compliance', 'agent']);
        Sanctum::actingAs($user);

        Account::factory()->count(10)->create();
        Account::factory()->count(5)->create([
            'is_active' => false
        ]);

        $this->json('get', '/api/accounts')
            ->assertOk()
            ->assertJsonCount(10, 'data')
            ->assertJsonStructure([
                'data' => [
                    [
                        'id',
                        'is_active',
                        'country' => [],
                        'currency' => [],
                        'bank' => [],
                        'label',
                        'bank_name',
                        'bank_account',
                        'account_name',
                        'account_type',
                        'description',
                        'document_number',
                        'created_at',
                        'updated_at'
                    ]
                ]
            ]);
    }


    /** @test */
    public function can_query_public_index_of_accounts()
    {
        $user = User::factory()->create();
        $user->assignRole(['user', 'base', 'admin', 'super_admin', 'compliance', 'agent']);
        Sanctum::actingAs($user);

        $currency = Currency::factory()->create();
        Account::factory()->count(5)->create([
            'currency_id' => $currency->id,
            'is_active' => true
        ]);

        $country = Country::factory()->create();
        Account::factory()->count(6)->create([
            'country_id' => $country->id,
            'is_active' => true
        ]);

        $this->json('get', '/api/accounts?currency_id=' . $currency->id)
            ->assertOk()
            ->assertJsonCount(5, 'data');

        $this->json('get', '/api/accounts?country_id=' . $country->id)
            ->assertOk()
            ->assertJsonCount(6, 'data');
    }

    /** @test */
    public function non_authenticated_user_cannot_view_an_index_form_admin()
    {
        $this->json('get', '/api/admin/accounts')
            ->assertUnauthorized();
    }

    /** @test */
    public function non_admin_user_cannot_view_index_of_accounts_for_admin()
    {
        $user = User::factory()->create();
        $user->assignRole(['user', 'base', 'super_admin', 'compliance', 'agent']);
        Sanctum::actingAs($user);

        $this->json('get', '/api/admin/accounts')
            ->assertForbidden();
    }

    /** @test */
    public function admin_user_can_view_index_of_accounts_for_admin()
    {
        $user = User::factory()->create();
        $user->assignRole(['base', 'admin']);
        Sanctum::actingAs($user);

        Account::factory()->count(10)->create();
        Account::factory()->count(5)->create([
            'is_active' => false
        ]);

        $this->json('get', '/api/admin/accounts')
            ->assertOk()
            ->assertJsonCount(15, 'data')
            ->assertJsonStructure([
                'data' => [
                    [
                        'is_active',
                        'country' => [],
                        'currency' => [],
                        'bank' => [],
                        'label',
                        'bank_name',
                        'bank_account',
                        'account_name',
                        'account_type',
                        'description',
                        'document_number',
                        'created_at',
                        'updated_at'
                    ]
                ]
            ]);
    }

    /** @test */
    public function non_authenticated_user_cannot_create_an_account()
    {
        $this->json('post', '/api/admin/accounts')
            ->assertUnauthorized();
    }

    /** @test */
    public function non_admin_user_cannot_create_an_account()
    {
        $user = User::factory()->create();
        $user->assignRole(['user', 'base', 'super_admin', 'compliance', 'agent']);
        Sanctum::actingAs($user);

        $this->json('post', '/api/admin/accounts')
            ->assertForbidden();
    }

    /** @test */
    public function admin_user_can_create_an_account()
    {
        $user = User::factory()->create();
        $user->assignRole(['user', 'admin']);
        Sanctum::actingAs($user);

        $country = Country::factory()->create();
        $currency = Currency::factory()->create();
        $bank = Bank::factory()->create();
        $account = Account::factory()->raw([
            'country_id' => $country->id,
            'currency_id' => $currency->id,
            'bank_id' => $bank->id
        ]);

        $this->json('post', '/api/admin/accounts', $account)
            ->assertCreated();

        $this->assertDatabaseHas('accounts', [
            'is_active' => $account['is_active'],
            'country_id' => $account['country_id'],
            'currency_id' => $account['currency_id'],
            'bank_id' => $account['bank_id'],
            'label' => $account['label'],
            'bank_name' => $account['bank_name'],
            'bank_account' => $account['bank_account'],
            'account_name' => $account['account_name'],
            'description' => $account['description'],
            'document_number' => $account['document_number'],
            'account_type' => $account['account_type']
        ]);
    }

    /** @test */
    public function cannot_create_an_account_without_required_fields()
    {
        $user = User::factory()->create();
        $user->assignRole(['user', 'admin']);
        Sanctum::actingAs($user);

        $account = Account::factory()->raw([
            'country_id' => null,
            'currency_id' => null,
            'label' => null,
            'bank_account' => null,
            'account_name' => null,
            'document_number' => null
        ]);

        $this->json('post', '/api/admin/accounts', $account)
            ->assertStatus(422)
            ->assertJsonFragment(['The country id field is required.'])
            ->assertJsonFragment(['The currency id field is required.'])
            ->assertJsonFragment(['The label field is required.'])
            ->assertJsonFragment(['The bank account field is required.'])
            ->assertJsonFragment(['The document number field is required.'])
            ->assertJsonFragment(['The account name field is required.']);
    }

    /** @test */
    public function to_create_an_account_currency_and_country_must_exists()
    {
        $user = User::factory()->create();
        $user->assignRole(['user', 'admin']);
        Sanctum::actingAs($user);

        $account = Account::factory()->raw([
            'country_id' => 100,
            'currency_id' => 100
        ]);

        $this->json('post', '/api/admin/accounts', $account)
            ->assertStatus(422)
            ->assertJsonFragment(['The selected country id is invalid.'])
            ->assertJsonFragment(['The selected currency id is invalid.']);
    }

    /** @test */
    public function non_authenticated_user_cannot_update_an_account()
    {
        $this->json('put', '/api/admin/accounts/100')
            ->assertUnauthorized();
    }

    /** @test */
    public function cannot_update_non_existing_account()
    {
        $user = User::factory()->create();
        $user->assignRole(['user', 'base', 'super_admin', 'compliance', 'agent']);
        Sanctum::actingAs($user);

        $this->json('put', '/api/admin/accounts/100')
            ->assertNotFound();
    }

    /** @test */
    public function non_admin_user_cannot_update_an_account()
    {
        $user = User::factory()->create();
        $user->assignRole(['user', 'base', 'super_admin', 'compliance', 'agent']);
        Sanctum::actingAs($user);

        $account = Account::factory()->create();

        $this->json('put', '/api/admin/accounts/' . $account->id)
            ->assertForbidden();
    }

    /** @test */
    public function an_admin_user_can_update_an_account()
    {
        $user = User::factory()->create();
        $user->assignRole(['user', 'admin']);
        Sanctum::actingAs($user);

        $account = Account::factory()->create();

        $this->json('put', '/api/admin/accounts/' . $account->id, [
            'is_active' => false,
            'country_id' => Country::factory()->create()->id,
            'currency_id' => $currency_id = Currency::factory()->create()->id,
            'bank_id' => $bank_id = Bank::factory()->create()->id,
            'label' => 'new label',
            'bank_name' => 'new name',
            'bank_account' => '123567890',
            'account_name' => 'Jhon Doe',
            'description' => 'New description',
            'account_type' => 'new account type',
            'document_number' => '125362748'
        ])->assertOk();

        $this->assertDatabaseHas('accounts', [
            'is_active' => false,
            'currency_id' => $currency_id,
            'bank_id' => $bank_id,
            'label' => 'new label',
            'bank_name' => 'new name',
            'bank_account' => '123567890',
            'account_name' => 'Jhon Doe',
            'description' => 'New description',
            'document_number' => '125362748',
            'account_type' => 'new account type'
        ]);
    }

    /** @test */
    public function non_authenticated_user_cannot_view_a_single_account()
    {
        $this->json('get', '/api/admin/accounts/100')
            ->assertUnauthorized();
    }

    /** @test */
    public function cannot_see_non_existing_account()
    {
        $user = User::factory()->create();
        $user->assignRole(['user', 'base', 'super_admin', 'compliance', 'agent']);
        Sanctum::actingAs($user);

        $this->json('get', '/api/admin/accounts/100')
            ->assertNotFound();
    }

    /** @test */
    public function non_admin_usr_cannot_view_a_single_account()
    {
        $user = User::factory()->create();
        $user->assignRole(['user', 'base', 'super_admin', 'compliance', 'agent']);
        Sanctum::actingAs($user);

        $account = Account::factory()->create();

        $this->json('get', '/api/admin/accounts/' . $account->id)
            ->assertForbidden();
    }

    /** @test */
    public function an_admin_user_can_view_a_single_account()
    {
        $user = User::factory()->create();
        $user->assignRole(['base', 'admin']);
        Sanctum::actingAs($user);

        $account = Account::factory()->create();

        $this->json('get', '/api/admin/accounts/' . $account->id)
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'is_active',
                    'country' => [],
                    'currency' => [],
                    'bank' => [],
                    'label',
                    'bank_name',
                    'bank_account',
                    'account_name',
                    'account_type',
                    'description',
                    'document_number',
                    'created_at',
                    'updated_at'
                ]
            ]);
    }

    /** @test */
    public function non_authenticated_user_cannot_create_secret_key_to_an_account()
    {
        $this->json('post', '/api/admin/accounts/100/secret-key')
            ->assertUnauthorized();
    }

    /** @test */
    public function cannot_create_secret_key_to_no_existing_account()
    {
        $user = User::factory()->create();
        $user->assignRole(['user', 'base', 'super_admin', 'compliance', 'agent']);
        Sanctum::actingAs($user);

        $this->json('post', '/api/admin/accounts/100/secret-key')
            ->assertNotFound();
    }

    /** @test */
    public function non_admin_user_cannot_create_secret_key()
    {
        $user = User::factory()->create();
        $user->assignRole(['user', 'base', 'super_admin', 'compliance', 'agent']);
        Sanctum::actingAs($user);

        $account = Account::factory()->create();

        $this->json('post', '/api/admin/accounts/' . $account->id . '/secret-key')
            ->assertForbidden();
    }

    /** @test */
    public function admin_user_can_create_secret_key_to_an_account()
    {
        $user = User::factory()->create();
        $user->assignRole(['admin']);
        Sanctum::actingAs($user);

        $account = Account::factory()->create();
        $this->assertNull($account->secret_key);

        $this->json('post', '/api/admin/accounts/' . $account->id . '/secret-key')
            ->assertCreated()
            ->assertJsonStructure([
                'data' => [
                    'secret_key'
                ]
            ]);

        $account->refresh();
        $this->assertNotNull($account->secret_key);
    }

    /** @test */
    public function non_authenticated_user_cannot_view_secret_key_of_an_account()
    {
        $this->json('get', '/api/admin/accounts/100/secret-key')
            ->assertUnauthorized();
    }

    /** @test */
    public function cannot_view_secret_key_of_non_existing_account()
    {
        $user = User::factory()->create();
        $user->assignRole(['user', 'base', 'super_admin', 'compliance', 'agent']);
        Sanctum::actingAs($user);

        $this->json('get', '/api/admin/accounts/100/secret-key')
            ->assertNotFound();
    }

    /** @test */
    public function not_admin_user_cannot_view_secret_key_of_an_account()
    {
        $user = User::factory()->create();
        $user->assignRole(['user', 'base', 'super_admin', 'compliance', 'agent']);
        Sanctum::actingAs($user);

        $account = Account::factory()->create();

        $this->json('get', '/api/admin/accounts/' . $account->id . '/secret-key')
            ->assertForbidden();
    }

    /** @test */
    public function admin_user_can_view_secret_key()
    {
        $user = User::factory()->create();
        $user->assignRole(['admin']);
        Sanctum::actingAs($user);

        $account = Account::factory()->create([
            'secret_key' => 'abc1234'
        ]);

        $this->json('get', '/api/admin/accounts/' . $account->id . '/secret-key')
            ->assertOk()
            ->assertJsonFragment([
                'data' => [
                    'secret_key' => 'abc1234'
                ]
            ]);
    }

    /** @test */
    public function if_no_secret_key_return_null()
    {
        $user = User::factory()->create();
        $user->assignRole(['admin']);
        Sanctum::actingAs($user);

        $account = Account::factory()->create();

        $this->json('get', '/api/admin/accounts/' . $account->id . '/secret-key')
            ->assertOk()
            ->assertJsonFragment([
                'data' => [
                    'secret_key' => null
                ]
            ]);
    }
}
