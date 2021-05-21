<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Company;
use Laravel\Sanctum\Sanctum;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class CompanyTest extends TestCase
{
    use DatabaseMigrations, RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }


    /** @test */
    public function non_authenticated_user_cannot_create_company_info()
    {
        $this->json('post', '/api/users/company')
            ->assertUnauthorized();
    }

    /** @test */
    public function an_user_with_non_user_role_cannot_create_a_company_info()
    {
        $user = User::factory()->create();
        $user->assignRole(['base', 'admin', 'super_admin', 'compliance', 'agent']);
        Sanctum::actingAs($user);

        $this->json('post', '/api/users/company')
            ->assertForbidden();
    }

    /** @test */
    public function an_user_with_user_role_and_corporative_type_can_create_a_company()
    {
        $user = User::factory()->create(['account_type' => 'corporative']);
        $user->assignRole(['user']);
        Sanctum::actingAs($user);

        $company = Company::factory()->raw();

        $this->json('post', '/api/users/company', $company)
            ->assertCreated();

        $this->assertDatabaseHas('companies', [
            'user_id' => $user->id,
            'name' => $company['name'],
            'id_number' => $company['id_number'],
            'activity' => $company['activity'],
            'country_id' => $company['country_id'],
            'has_politician_history' => $company['has_politician_history'],
            'politician_history_charge' => $company['politician_history_charge'],
            'politician_history_country_id' => $company['politician_history_country_id'],
            'politician_history_from' => $company['politician_history_from'],
            'politician_history_to' => $company['politician_history_to'],
            'activities' => $company['activities'],
            'anual_revenues' => $company['anual_revenues'],
            'company_size' => $company['company_size'],
            'funds_origins' => $company['funds_origins'],
            'verified_at' => $company['verified_at'],
            'rejected_at' => $company['rejected_at'],
            'rejection_reasons' => $company['rejection_reasons'],
        ]);
    }

    /** @test */
    public function cannot_create_an_company_if_the_user_has_personal_account()
    {
        $user = User::factory()->create(['account_type' => 'personal']);
        $user->assignRole(['user']);
        Sanctum::actingAs($user);

        $company = Company::factory()->raw();

        $this->json('post', '/api/users/company', $company)
            ->assertForbidden();
    }

    /** @test */
    public function cannot_create_a_company_without_required_fields()
    {
        $user = User::factory()->create(['account_type' => 'corporative']);
        $user->assignRole(['user']);
        Sanctum::actingAs($user);

        $company = Company::factory()->raw([
            'name' => null,
            'id_number' => null,
            'activity' => null,
            'country_id' => null,
        ]);

        $this->json('post', '/api/users/company', $company)
            ->assertStatus(422)
            ->assertJsonFragment(['The name field is required.'])
            ->assertJsonFragment(['The id number field is required.'])
            ->assertJsonFragment(['The activity field is required.'])
            ->assertJsonFragment(['The country id field is required.']);
    }

    /** @test */
    public function country_must_exists()
    {
        $user = User::factory()->create(['account_type' => 'corporative']);
        $user->assignRole(['user']);
        Sanctum::actingAs($user);

        $company = Company::factory()->raw([
            'country_id' => 100,
            'politician_history_country_id' => 100,
        ]);

        $this->json('post', '/api/users/company', $company)
            ->assertStatus(422)
            ->assertJsonFragment(['The selected country id is invalid.'])
            ->assertJsonFragment(['The selected politician history country id is invalid.']);
    }

    /** @test */
    public function anual_revenues_must_be_valid()
    {
        $user = User::factory()->create(['account_type' => 'corporative']);
        $user->assignRole(['user']);
        Sanctum::actingAs($user);

        $company = Company::factory()->raw(['anual_revenues' => 'LT_100M_USD']);
        $this->json('post', '/api/users/company', $company)
            ->assertCreated();

        $company = Company::factory()->raw(['anual_revenues' => 'LT_1MM_USD']);
        $this->json('post', '/api/users/company', $company)
            ->assertCreated();

        $company = Company::factory()->raw(['anual_revenues' => 'LT_4MM_USD']);
        $this->json('post', '/api/users/company', $company)
            ->assertCreated();

        $company = Company::factory()->raw(['anual_revenues' => 'GT_4MM_USD']);
        $this->json('post', '/api/users/company', $company)
            ->assertCreated();

        $company = Company::factory()->raw(['anual_revenues' => 'some']);
        $this->json('post', '/api/users/company', $company)
            ->assertStatus(422)
            ->assertJsonFragment(['The selected anual revenues is invalid.']);
    }

    /** @test */
    public function company_size_must_be_valid()
    {
        $user = User::factory()->create(['account_type' => 'corporative']);
        $user->assignRole(['user']);
        Sanctum::actingAs($user);

        $company = Company::factory()->raw(['company_size' => 'micro']);
        $this->json('post', '/api/users/company', $company)
            ->assertCreated();

        $company = Company::factory()->raw(['company_size' => 'small']);
        $this->json('post', '/api/users/company', $company)
            ->assertCreated();

        $company = Company::factory()->raw(['company_size' => 'mid']);
        $this->json('post', '/api/users/company', $company)
            ->assertCreated();

        $company = Company::factory()->raw(['company_size' => 'large']);
        $this->json('post', '/api/users/company', $company)
            ->assertCreated();

        $company = Company::factory()->raw(['company_size' => 'some']);
        $this->json('post', '/api/users/company', $company)
            ->assertStatus(422)
            ->assertJsonFragment(['The selected company size is invalid.']);
    }

    /** @test */
    public function non_authenticated_user_cannot_verify_a_company()
    {
        $this->json('post', '/api/admin/users/100/verify-company')
            ->assertUnauthorized();
    }

    /** @test */
    public function cannot_verify_non_existing_user()
    {
        $user = User::factory()->create();
        $user->assignRole(['user', 'base', 'admin', 'super_admin', 'agent']);
        Sanctum::actingAs($user);

        $this->json('post', '/api/admin/users/100/verify-company')
            ->assertNotFound();
    }

    /** @test */
    public function non_compliance_user_cannot_verify_an_account()
    {
        $user = User::factory()->create();
        $user->assignRole(['user', 'base', 'admin', 'super_admin', 'agent']);
        Sanctum::actingAs($user);

        $userToVerify = User::factory()->create(['account_type' => 'corporative']);
        $userToVerify->assignRole('user');
        Company::factory()->create([
            'user_id' => $userToVerify->id
        ]);

        $this->json('post', '/api/admin/users/'. $userToVerify->id .'/verify-company')
            ->assertForbidden();
    }

    /** @test */
    public function compliance_user_can_verify_a_company()
    {
        $user = User::factory()->create();
        $user->assignRole(['compliance']);
        Sanctum::actingAs($user);

        $userToVerify = User::factory()->create(['account_type' => 'corporative']);
        $userToVerify->assignRole('user');
        Company::factory()->create([
            'user_id' => $userToVerify->id
        ]);

        $this->json('post', '/api/admin/users/'. $userToVerify->id .'/verify-company')
            ->assertOk();

        $userToVerify->refresh();
        $this->assertNotNull($userToVerify->company->verified_at);
        $this->assertNull($userToVerify->company->rejected_at);
    }

    /** @test */
    public function cannot_verifiy_a_company_that_does_not_exists()
    {
        $user = User::factory()->create();
        $user->assignRole(['compliance']);
        Sanctum::actingAs($user);

        $userToVerify = User::factory()->create(['account_type' => 'corporative']);
        $userToVerify->assignRole('user');

        $this->json('post', '/api/admin/users/'. $userToVerify->id .'/verify-company')
            ->assertNotFound();
    }

    /** @test */
    public function cannot_verify_a_company_of_a_user_with_person_account()
    {
        $user = User::factory()->create();
        $user->assignRole(['compliance']);
        Sanctum::actingAs($user);

        $userToVerify = User::factory()->create(['account_type' => 'personal']);
        $userToVerify->assignRole('user');
        Company::factory()->create([
            'user_id' => $userToVerify->id
        ]);

        $this->json('post', '/api/admin/users/'. $userToVerify->id .'/verify-company')
            ->assertForbidden();

        $userToVerify->refresh();
        $this->assertNull($userToVerify->company->verified_at);
    }

    /** @test */
    public function non_authenticated_user_cannot_reject_a_company()
    {
        $this->json('post', '/api/admin/users/100/reject-company')
            ->assertUnauthorized();
    }

    /** @test */
    public function cannot_reject_a_company_of_a_user_that_does_not_exist()
    {
        $user = User::factory()->create();
        $user->assignRole(['user', 'base', 'admin', 'super_admin', 'agent']);
        Sanctum::actingAs($user);

        $this->json('post', '/api/admin/users/100/reject-company')
            ->assertNotFound();
    }

    /** @test */
    public function non_compliance_user_cannot_reject_an_account()
    {
        $user = User::factory()->create();
        $user->assignRole(['user', 'base', 'admin', 'super_admin', 'agent']);
        Sanctum::actingAs($user);

        $userToVerify = User::factory()->create(['account_type' => 'corporative']);
        $userToVerify->assignRole('user');
        Company::factory()->create([
            'user_id' => $userToVerify->id
        ]);

        $this->json('post', '/api/admin/users/'. $userToVerify->id .'/reject-company')
            ->assertForbidden();
    }

    /** @test */
    public function compliance_user_can_reject_a_company()
    {
        $user = User::factory()->create();
        $user->assignRole(['compliance']);
        Sanctum::actingAs($user);

        $userToVerify = User::factory()->create(['account_type' => 'corporative']);
        $userToVerify->assignRole('user');
        Company::factory()->create([
            'user_id' => $userToVerify->id
        ]);

        $this->json('post', '/api/admin/users/'. $userToVerify->id .'/reject-company')
            ->assertOk();

        $userToVerify->refresh();
        $this->assertNull($userToVerify->company->verified_at);
        $this->assertNotNull($userToVerify->company->rejected_at);
    }

    /** @test */
    public function cannot_reject_a_compnay_that_does_not_exists()
    {
        $user = User::factory()->create();
        $user->assignRole(['compliance']);
        Sanctum::actingAs($user);

        $userToVerify = User::factory()->create(['account_type' => 'corporative']);
        $userToVerify->assignRole('user');

        $this->json('post', '/api/admin/users/'. $userToVerify->id .'/reject-company')
            ->assertNotFound();

        $userToVerify->refresh();
        $this->assertNull($userToVerify->rejected_at);
    }
}
