<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\AccountIncome;
use Tests\TestCase;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class AccountIncomeTest extends TestCase
{
    use DatabaseMigrations, RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    /** @test */
    public function non_authenticated_user_cannot_view_account_incomes()
    {
        $this->json('get', '/api/admin/accounts/1/incomes')
            ->assertUnauthorized();
    }

    /** @test */
    public function cannot_view_account_incomes_to_no_existing_account()
    {
        $user = User::factory()->create();
        $user->assignRole(['user', 'base', 'super_admin', 'compliance', 'agent']);
        Sanctum::actingAs($user);

        $this->json('get', '/api/admin/accounts/100/incomes')
            ->assertNotFound();
    }

    /** @test */
    public function non_admin_user_cannot_view_account_incomes()
    {
        $user = User::factory()->create();
        $user->assignRole(['user', 'base', 'super_admin', 'compliance', 'agent']);
        Sanctum::actingAs($user);

        $account = Account::factory()->create();

        $this->json('get', "/api/admin/accounts/$account->id/incomes")
            ->assertForbidden();
    }

    /** @test */
    public function admin_user_can_view_index_of_account_incomes()
    {
        $user = User::factory()->create();
        $user->assignRole(['base', 'admin']);
        Sanctum::actingAs($user);

        $account = Account::factory()->create();
        AccountIncome::factory()->count(10)->create(['account_id' => $account->id]);

        $this->json('get', "/api/admin/accounts/$account->id/incomes")
            ->assertOk()
            ->assertJsonCount(10, 'data')
            ->assertJsonStructure([
                'data' => [
                    [
                        'id',
                        'account' => [],
                        'user' => [],
                        'origin',
                        'transaction_number',
                        'transaction_date',
                        'amount',
                    ]
                ],
                'meta' => [],
                'links' => []
            ]);
    }

    // /** @test */
    // public function non_authenticated_user_cannot_create_an_income_manually()
    // {
    //     $this->json('post', '/api/admin/accounts/100/incomes')
    //         ->assertUnauthorized();
    // }

    // /** @test */
    // public function cannot_create_an_income_to_non_existing_account()
    // {
    //     $user = User::factory()->create();
    //     $user->assignRole(['user', 'base', 'super_admin', 'compliance', 'agent']);
    //     Sanctum::actingAs($user);

    //     $this->json('post', '/api/admin/accounts/100/incomes')
    //         ->assertNotFound();
    // }

    // /** @test */
    // public function non_admin_user_cannot_create_an_income()
    // {
    //     $user = User::factory()->create();
    //     $user->assignRole(['user', 'base', 'super_admin', 'compliance', 'agent']);
    //     Sanctum::actingAs($user);

    //     $account = Account::factory()->create();

    //     $this->json('post', "/api/admin/accounts/$account->id/incomes")
    //         ->assertForbidden();
    // }

    // /** @test */
    // public function admin_user_can_create_an_income()
    // {
    //     $user = User::factory()->create();
    //     $user->assignRole(['base', 'admin']);
    //     Sanctum::actingAs($user);

    //     $account = Account::factory()->create();
    //     $income = AccountIncome::factory()->raw(['account_id' => $account->id]);

    //     $this->json('post', "/api/admin/accounts/$account->id/incomes", $income)
    //         ->assertCreated();

    //     $this->assertDatabaseHas('account_incomes', [
    //         'account_id' => $income['account_id'],
    //         'user_id' => $income['user_id'],
    //         'origin' => $income['origin'],
    //         'transaction_number' => $income['transaction_number'],
    //         'amount' => $income['amount']
    //     ]);
    // }
}
