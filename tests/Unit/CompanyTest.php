<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Company;
use App\Models\Country;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class CompanyTest extends TestCase
{
    use DatabaseMigrations, RefreshDatabase;

    /** @test */
    public function can_create_a_company()
    {
        $timestamp = now();
        Company::create([
            'user_id' => 1,
            'name' => 'Company Name',
            'id_number' => '83128301293',
            'activity' => 'Company Activiy',
            'country_id' => 1,
            'has_politician_history' => true,
            'politician_history_charge' => 'Charge',
            'politician_history_country_id' => 1,
            'politician_history_from' => '2015-01-01',
            'politician_history_to' => '2016-01-01',
            'activities' => 'Company activities',
            'anual_revenues' => 'GT_4MM_USD',
            'company_size' => 'large',
            'fund_origins' => "['uno', 'dos]",
            'address' => 'fake av. 123',
            'verified_at' => $timestamp,
            'rejected_at' => $timestamp,
            'rejection_reasons' => 'lorem ipsum'
        ]);

        $this->assertDatabaseHas('companies', [
            'user_id' => 1,
            'name' => 'Company Name',
            'id_number' => '83128301293',
            'activity' => 'Company Activiy',
            'country_id' => 1,
            'has_politician_history' => true,
            'politician_history_charge' => 'Charge',
            'politician_history_country_id' => 1,
            'politician_history_from' => '2015-01-01',
            'politician_history_to' => '2016-01-01',
            'activities' => 'Company activities',
            'anual_revenues' => 'GT_4MM_USD',
            'company_size' => 'large',
            'fund_origins' => "['uno', 'dos]",
            'address' => 'fake av. 123',
            'verified_at' => $timestamp,
            'rejected_at' => $timestamp,
            'rejection_reasons' => 'lorem ipsum'
        ]);
    }

    /** @test */
    public function a_company_belongs_to_a_user()
    {
        $user = User::factory()->create();
        $company = Company::factory()->create([
            'user_id' => $user->id
        ]);

        $this->assertInstanceOf(Company::class, $user->company);
        $this->assertInstanceOf(User::class, $company->user);
    }

    /** @test */
    public function a_company_belongs_to_a_country()
    {
        $country = Country::factory()->create();
        $company = Company::factory()->create([
            'country_id' => $country->id
        ]);

        $this->assertInstanceOf(Country::class, $company->country);
    }

    /** @test */
    public function a_company_belongs_to_a_country_history()
    {
        $country = Country::factory()->create();
        $company = Company::factory()->create([
            'politician_history_country_id' => $country->id
        ]);

        $this->assertInstanceOf(Country::class, $company->politician_history_country);
    }
}
