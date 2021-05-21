<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Account;
use App\Models\Identity;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class BalanceControllerTest extends TestCase
{
    use RefreshDatabase, DatabaseMigrations, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    /** @test */
    public function cant_receive_a_request_of_incoming_balance()
    {
        $secret_key = 'abc123';
        $account = Account::factory()->create([
            'secret_key' => $secret_key
        ]);
        $user = User::factory()->create(['balance' => 0]);
        Identity::factory()->create(['identity_number' => '123456789', 'user_id' => $user->id]);

        $request = $this->createRequest($account->id, 10, now()->toISOString());
        $payload = $request['external_id'] . $request['transaction_date'] . $request['payin_id'] . $request['origin_id'] . $request['amount'];
        $request['authorization'] = hash_hmac('sha256', $payload, $secret_key);
        $this->json('post', '/api/bank-transfer', $request)
            ->assertCreated()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'user' => [
                        'id',
                        'username',
                        'email',
                        'account_type',
                        'status',
                        'is_agent'
                    ],
                    'account',
                    'currency',
                    'external_id',
                    'type',
                    'amount',
                    'note',
                    'transaction_date',
                    'created_at',
                    'updated_at'
                ]
            ])
            ->assertJsonFragment(['email' => $user->email])
            ->assertJsonFragment(['external_id' => $request['payin_id']]);

        $this->assertDatabaseHas('transactions', [
            'user_id' => $user->id,
            'external_id' => $request['payin_id'],
            'type' => 'income',
            'amount' => $request['amount']
        ]);

        $user->refresh();
        $this->assertEquals($request['amount'], $user->balance);
    }

    /** @test */
    public function cannot_process_a_transaction_income_if_the_authorization_is_null()
    {
        $secret_key = 'abc123';
        $account = Account::factory()->create([
            'secret_key' => $secret_key
        ]);
        $user = User::factory()->create(['balance' => 0]);
        Identity::factory()->create(['identity_number' => '123456789', 'user_id' => $user->id]);

        $request = $this->createRequest($account->id);
        $this->json('post', '/api/bank-transfer', $request)
            ->assertStatus(422)
            ->assertJsonFragment(['The authorization field is required.']);
    }

    /** @test */
    public function cannot_process_a_transaction_income_if_the_authorization_is_invalid()
    {
        $secret_key = 'abc123';
        $account = Account::factory()->create([
            'secret_key' => $secret_key
        ]);
        $user = User::factory()->create(['balance' => 0]);
        Identity::factory()->create(['identity_number' => '123456789', 'user_id' => $user->id]);

        $request = $this->createRequest($account->id, 10, now()->toISOString());
        $payload = $request['external_id'] . $request['transaction_date'] . $request['payin_id'] . $request['origin_id'] . $request['amount'];
        $request['authorization'] = hash_hmac('sha256', $payload, 'abc12341');
        $this->json('post', '/api/bank-transfer', $request)
            ->assertForbidden();
    }

    /** @test */
    public function cannot_process_a_transaction_without_required_fields()
    {
        $user = User::factory()->create(['balance' => 0]);
        Identity::factory()->create(['identity_number' => '123456789', 'user_id' => $user->id]);

        $this->json('post', '/api/bank-transfer', [
            'external_id' => null,
            'transaction_date' => null,
            'payin_id' => null,
            'amount' => null,
            'origin_id' => null,
        ])->assertStatus(422)
            ->assertJsonFragment(['The external id field is required.'])
            ->assertJsonFragment(['The transaction date field is required.'])
            ->assertJsonFragment(['The payin id field is required.'])
            ->assertJsonFragment(['The amount field is required.'])
            ->assertJsonFragment(['The origin id field is required.']);
    }

    /** @test */
    public function cannot_process_a_transaction_if_the_external_id_aka_account_does_not_exists()
    {
        $secret_key = 'abc123';
        $user = User::factory()->create(['balance' => 0]);
        Identity::factory()->create(['identity_number' => '123456789', 'user_id' => $user->id]);

        $request = $this->createRequest(100);
        $payload = $request['external_id'] . $request['transaction_date'] . $request['payin_id'] . $request['origin_id'] . $request['amount'];
        $request['authorization'] = hash_hmac('sha256', $payload, $secret_key);
        $this->json('post', '/api/bank-transfer', $request)
            ->assertStatus(422)
            ->assertJsonFragment(['The selected external id is invalid.']);
    }

    /** @test */
    public function cannot_process_a_transaction_if_the_transaction_date_is_before_yestarday()
    {
        $secret_key = 'abc123';
        $account = Account::factory()->create([
            'secret_key' => $secret_key
        ]);
        $user = User::factory()->create(['balance' => 0]);
        Identity::factory()->create(['identity_number' => '123456789', 'user_id' => $user->id]);

        $request = $this->createRequest($account->id, 10, now()->subDays(3)->toISOString());
        $payload = $request['external_id'] . $request['transaction_date'] . $request['payin_id'] . $request['origin_id'] . $request['amount'];
        $request['authorization'] = hash_hmac('sha256', $payload, $secret_key);
        $this->json('post', '/api/bank-transfer', $request)
            ->assertStatus(422)
            ->assertJsonFragment(['The transaction date must be a date after or equal to yesterday.']);
    }

    /** @test */
    public function cannot_process_a_transaction_if_the_transaction_date_is_after_today()
    {
        $secret_key = 'abc123';
        $account = Account::factory()->create([
            'secret_key' => $secret_key
        ]);
        $user = User::factory()->create(['balance' => 0]);
        Identity::factory()->create(['identity_number' => '123456789', 'user_id' => $user->id]);

        $request = $this->createRequest($account->id, 10, now()->addDay()->toISOString());
        $payload = $request['external_id'] . $request['transaction_date'] . $request['payin_id'] . $request['origin_id'] . $request['amount'];
        $request['authorization'] = hash_hmac('sha256', $payload, $secret_key);
        $this->json('post', '/api/bank-transfer', $request)
            ->assertStatus(422)
            ->assertJsonFragment(['The transaction date must be a date before tomorrow.']);
    }

    protected function createRequest($external_id=1, $account_id=10, $transaction_date='01/01/2021', $payin_id='abc123', $origin_id='123456789', $amount=30000)
    {
        $request['external_id'] = $external_id;
        $request['account_id'] = $account_id;
        $request['transaction_date'] = $transaction_date;
        $request['payin_id'] = $payin_id;
        $request['origin_id'] = $origin_id;
        $request['amount'] = $amount;
        return $request;
    }
}
