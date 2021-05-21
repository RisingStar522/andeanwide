<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Account;
use App\Models\AccountIncome;
use App\Models\Identity;
use App\Models\Transaction;
use Laravel\Sanctum\Sanctum;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class TransactionTest extends TestCase
{
    use DatabaseMigrations, RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    /** @test */
    public function non_authenticated_user_cannot_view_transactions_index()
    {
        $this->json('get', '/api/admin/transactions')
            ->assertUnauthorized();
    }

    /** @test */
    public function non_admin_users_cannot_view_transactions_index()
    {
        $user = User::factory()->create();
        $user->assignRole(['user', 'base', 'super_admin', 'compliance', 'agent']);
        Sanctum::actingAs($user);

        $this->json('get', '/api/admin/transactions')
            ->assertForbidden();
    }

    /** @test */
    public function admin_user_can_view_transactions()
    {
        $user = User::factory()->create();
        $user->assignRole(['admin']);
        Sanctum::actingAs($user);

        Transaction::factory()->count(15)->create();

        $this->json('get', '/api/admin/transactions')
            ->assertOk()
            ->assertJsonCount(15, 'data')
            ->assertJsonStructure([
                'data' => [
                    [
                        'id',
                        'account',
                        'order',
                        'user',
                        'external_id',
                        'type',
                        'currency',
                        'note',
                        'rejected_at',
                        'transaction_date'
                    ]
                ],
                'meta' => [],
                'links' => []
            ]);
    }

    /** @test */
    public function non_authentication_cannot_view_a_single_transaction()
    {
        $this->json('get', '/api/admin/transactions')
            ->assertUnauthorized();
    }

    /** @test */
    public function cannot_view_non_existing_transaction()
    {
        $user = User::factory()->create();
        $user->assignRole(['user', 'base', 'super_admin', 'compliance', 'agent']);
        Sanctum::actingAs($user);

        $this->json('get', '/api/admin/transactions/100')
            ->assertNotFound();
    }

    /** @test */
    public function non_admin_user_cannot_view_a_single_transaction()
    {
        $user = User::factory()->create();
        $user->assignRole(['user', 'base', 'super_admin', 'compliance', 'agent']);
        Sanctum::actingAs($user);

        $transaction = Transaction::factory()->create();

        $this->json('get', "/api/admin/transactions/$transaction->id")
            ->assertForbidden();
    }

    /** @test */
    public function admin_user_can_view_single_transaction()
    {
        $user = User::factory()->create();
        $user->assignRole(['admin']);
        Sanctum::actingAs($user);

        $transaction = Transaction::factory()->create();

        $this->json('get', "/api/admin/transactions/$transaction->id")
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'account',
                    'order',
                    'user',
                    'external_id',
                    'type',
                    'currency',
                    'note',
                    'rejected_at',
                    'transaction_date'
                ]
            ]);
    }

    /** @test */
    public function non_authenticated_user_cannot_create_a_transaction()
    {
        $this->json('post', '/api/admin/transactions')
            ->assertUnauthorized();
    }

    /** @test */
    public function non_admin_user_cannot_create_transaction()
    {
        $user = User::factory()->create();
        $user->assignRole(['user', 'base', 'super_admin', 'compliance', 'agent']);
        Sanctum::actingAs($user);

        $this->json('post', "/api/admin/transactions")
            ->assertForbidden();
    }

    /** @test */
    public function admin_user_can_create_transaction()
    {
        $user = User::factory()->create();
        $user->assignRole(['admin']);
        Sanctum::actingAs($user);

        $account = Account::factory()->create();
        $client = User::factory()->create(['balance' => 20000]);
        Identity::factory()->create(['user_id' => $client->id]);
        $transaction = Transaction::factory()->raw([
            'account_id' => $account->id,
            'user_id' => $client->id,
        ]);

        $this->json('post', "/api/admin/transactions", $transaction)
            ->assertCreated();

        $this->assertDatabaseHas('transactions', [
            'user_id' => $client->id,
            'account_id' => $account->id,
            'order_id' => $transaction['order_id'],
            'external_id' => $transaction['external_id'],
            'amount' => $transaction['amount'],
            'currency_id' => $account->currency_id,
            'type' => 'income',
            'note' => $transaction['note'],
            'rejected_at' => null,
        ]);

        $this->assertDatabaseHas('account_incomes', [
            'account_id' => $account->id,
            'user_id' => $client->id,
            'origin' => $client->identity->identity_number,
            'transaction_number' => $transaction['external_id'],
            'amount' => $transaction['amount'],
            'rejected_at' => null
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $client->id,
            'balance' => $client->balance + $transaction['amount']
        ]);
    }

    /** @test */
    public function cannot_create_a_transaction_without_required_fields()
    {
        $user = User::factory()->create();
        $user->assignRole(['admin']);
        Sanctum::actingAs($user);

        $transaction = Transaction::factory()->raw([
            'account_id' => null,
            'user_id' => null,
            'external_id' => null,
            'amount' => null,
            'transaction_date' => null
        ]);

        $this->json('post', '/api/admin/transactions', $transaction)
            ->assertStatus(422)
            ->assertJsonFragment(['The account id field is required.'])
            ->assertJsonFragment(['The user id field is required.'])
            ->assertJsonFragment(['The external id field is required.'])
            ->assertJsonFragment(['The amount field is required.'])
            ->assertJsonFragment(['The transaction date field is required.']);
    }

    /** @test */
    public function ids_must_exists()
    {
        $user = User::factory()->create();
        $user->assignRole(['admin']);
        Sanctum::actingAs($user);

        $transaction = Transaction::factory()->raw([
            'account_id' => 100,
            'user_id' => 200,
        ]);

        $this->json('post', '/api/admin/transactions', $transaction)
            ->assertStatus(422)
            ->assertJsonFragment(['The selected account id is invalid.'])
            ->assertJsonFragment(['The selected user id is invalid.']);
    }

    /** @test */
    public function transaction_date_must_be_a_valid_date()
    {
        $user = User::factory()->create();
        $user->assignRole(['admin']);
        Sanctum::actingAs($user);

        $account = Account::factory()->create();
        $client = User::factory()->create(['balance' => 20000]);
        Identity::factory()->create(['user_id' => $client->id]);
        $transaction = Transaction::factory()->raw([
            'account_id' => $account->id,
            'user_id' => $client->id,
            'transaction_date' => 'abc123'
        ]);

        $this->json('post', '/api/admin/transactions', $transaction)
            ->assertStatus(422)
            ->assertJsonFragment(['The transaction date is not a valid date.']);
    }

    /** @test */
    public function non_authenticated_user_cannot_reject_transaction()
    {
        $this->json('post', '/api/admin/transactions/100/reject')
            ->assertUnauthorized();
    }

    /** @test */
    public function cannot_view_transactions_that_does_not_exist()
    {
        $user = User::factory()->create();
        $user->assignRole(['user', 'base', 'super_admin', 'compliance', 'agent']);
        Sanctum::actingAs($user);

        $this->json('post', '/api/admin/transactions/100/reject')
            ->assertNotFound();
    }

    /** @test */
    public function non_admin_user_cannot_view_single_transaction()
    {
        $user = User::factory()->create();
        $user->assignRole(['user', 'base', 'super_admin', 'compliance', 'agent']);
        Sanctum::actingAs($user);

        $transaction = Transaction::factory()->create();

        $this->json('post', '/api/admin/transactions/' . $transaction->id . '/reject')
            ->assertForbidden();
    }

    /** @test */
    public function admin_user_can_reject_transaction()
    {
        $user = User::factory()->create();
        $user->assignRole(['admin']);
        Sanctum::actingAs($user);

        $client = User::factory()->create(['balance' => 20000]);
        $transaction = Transaction::factory()->create(['user_id' => $client->id]);
        $accountIncome = AccountIncome::factory()->create([
            'user_id' => $client->id,
            'transaction_id' => $transaction->id,
            'amount' => $transaction->amount
        ]);

        $this->json('post', '/api/admin/transactions/' . $transaction->id . '/reject')
            ->assertOk();

        $transaction->refresh();
        $this->assertNotNull($transaction->rejected_at);
        $accountIncome = AccountIncome::where('transaction_id', $transaction->id)->first();
        $this->assertNotNull($accountIncome->rejected_at);

        $this->assertDatabaseHas('users', [
            'id' => $client->id,
            'balance' => $client->balance - $transaction['amount']
        ]);
    }
}
