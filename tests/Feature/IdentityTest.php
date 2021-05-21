<?php

namespace Tests\Feature;

use App\Models\Country;
use Tests\TestCase;
use App\Models\User;
use App\Models\Identity;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class IdentityTest extends TestCase
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
    public function non_autehnticated_user_canno_create_an_identity()
    {
        $this->json('post', 'api/users/identity')
            ->assertUnauthorized();
    }

    /** @test */
    public function an_authenticated_user_can_create_an_identity()
    {
        $user = User::factory()->create();
        $user->assignRole('user');
        Sanctum::actingAs($user);

        $issuanceCountry = Country::factory()->create();
        $nationalityCountry = Country::factory()->create();
        $identity = Identity::factory()->raw([
            'user_id'               => null,
            'issuance_country'      => $issuanceCountry->id,
            'nationality_country'   => $nationalityCountry->id,
        ]);

        $this->json('post', 'api/users/identity', $identity)
            ->assertCreated();

        $this->assertDatabaseHas('identities', [
            'user_id'           => $user->id,
            'firstname'         => $identity['firstname'],
            'lastname'          => $identity['lastname'],
            'document_type'     => $identity['document_type'],
            'identity_number'   => $identity['identity_number'],
            'gender'            => $identity['gender'],
            'profession'        => $identity['profession'],
            'activity'          => $identity['activity'],
            'position'          => $identity['position'],
            'state'             => $identity['state'],
            'verified_at'       => null,
            'rejected_at'       => null,
        ]);
    }

    /** @test */
    public function an_user_cannot_create_an_identity_if_it_has_already_created_one()
    {
        $user = User::factory()->create();
        $user->assignRole('user');
        Sanctum::actingAs($user);

        Identity::factory()->create([
            'user_id'       => $user->id,
            'verified_at'   => null,
            'rejected_at'   => null
        ]);

        $identity = Identity::factory()->raw([
            'user_id'       => null
        ]);

        $this->json('post', 'api/users/identity', $identity)
            ->assertForbidden();
    }

    /** @test */
    public function an_user_can_create_an_identity_if_it_has_already_created_one_and_its_rejected()
    {
        $user = User::factory()->create();
        $user->assignRole('user');
        Sanctum::actingAs($user);

        Identity::factory()->create([
            'user_id'       => $user->id,
            'verified_at'   => null,
            'rejected_at'   => now()
        ]);

        $identity = Identity::factory()->raw([
            'user_id'       => null
        ]);

        $this->json('post', 'api/users/identity', $identity)
            ->assertCreated();
    }

    /** @test */
    public function an_admin_user_cannot_create_an_identity()
    {
        $user = User::factory()->create();
        $user->assignRole(['base', 'admin', 'super_admin', 'agent', 'compliance']);
        Sanctum::actingAs($user);

        $identity = Identity::factory()->raw([
            'user_id'   => null,
        ]);

        $this->json('post', 'api/users/identity', $identity)
            ->assertForbidden();
    }

    /** @test */
    public function cannot_create_an_identity_without_required_fields()
    {
        $user = User::factory()->create();
        $user->assignRole('user');
        Sanctum::actingAs($user);

        $identity = Identity::factory()->raw([
            'user_id'                   => null,
            'firstname'                 => null,
            'lastname'                  => null,
            'identity_number'           => null,
            'document_type'             => null,
            'dob'                       => null,
            'issuance_date'             => null,
            'expiration_date'           => null,
            'gender'                    => null,
            'issuance_country_id'       => null,
            'nationality_country_id'    => null
        ]);

        $this->json('post', 'api/users/identity', $identity)
            ->assertStatus(422)
            ->assertJsonFragment(['The firstname field is required.'])
            ->assertJsonFragment(['The lastname field is required.'])
            ->assertJsonFragment(['The identity number field is required.'])
            ->assertJsonFragment(['The document type field is required.'])
            ->assertJsonFragment(['The dob field is required.'])
            ->assertJsonFragment(['The issuance date field is required.'])
            ->assertJsonFragment(['The expiration date field is required.'])
            ->assertJsonFragment(['The gender field is required.'])
            ->assertJsonFragment(['The issuance country id field is required.'])
            ->assertJsonFragment(['The nationality country id field is required.']);
    }

    /** @test */
    public function selected_countries_must_exist()
    {
        $user = User::factory()->create();
        $user->assignRole('user');
        Sanctum::actingAs($user);

        $identity = Identity::factory()->raw([
            'user_id'                   => $user->id,
            'issuance_country_id'       => 100,
            'nationality_country_id'    => 100
        ]);

        $this->json('post', 'api/users/identity', $identity)
            ->assertStatus(422)
            ->assertJsonFragment(['The selected issuance country id is invalid.'])
            ->assertJsonFragment(['The selected nationality country id is invalid.']);
    }

    /** @test */
    public function cannot_create_an_identity_without_date_fields_with_datetime_formats()
    {
        $user = User::factory()->create();
        $user->assignRole('user');
        Sanctum::actingAs($user);

        $identity = Identity::factory()->raw([
            'user_id'           => null,
            'dob'               => 'string',
            'issuance_date'     => 'string',
            'expiration_date'   => 'string',
        ]);

        $this->json('post', 'api/users/identity', $identity)
            ->assertStatus(422)
            ->assertJsonFragment(['The dob is not a valid date.'])
            ->assertJsonFragment(['The issuance date is not a valid date.'])
            ->assertJsonFragment(['The expiration date is not a valid date.']);
    }

    /** @test */
    public function dob_must_be_greater_than_18_years_baack()
    {
        $user = User::factory()->create();
        $user->assignRole('user');
        Sanctum::actingAs($user);

        $identity = Identity::factory()->raw([
            'user_id'           => null,
            'dob'               => now()->subYears(17),
        ]);

        $this->json('post', 'api/users/identity', $identity)
            ->assertStatus(422)
            ->assertJsonFragment(['The dob must be a date before or equal to -18Years.']);
    }

    /** @test */
    public function issuance_date_must_be_less_than_today()
    {
        $user = User::factory()->create();
        $user->assignRole('user');
        Sanctum::actingAs($user);

        $identity = Identity::factory()->raw([
            'user_id'           => null,
            'issuance_date'     => now()->addDay(),
        ]);

        $this->json('post', 'api/users/identity', $identity)
            ->assertStatus(422)
            ->assertJsonFragment(['The issuance date must be a date before or equal to Today.']);
    }

    /** @test */
    public function expiration_date_must_be_after_today()
    {
        $user = User::factory()->create();
        $user->assignRole('user');
        Sanctum::actingAs($user);

        $identity = Identity::factory()->raw([
            'user_id'           => null,
            'expiration_date'   => now()->subDay(),
        ]);

        $this->json('post', 'api/users/identity', $identity)
            ->assertStatus(422)
            ->assertJsonFragment(['The expiration date must be a date after Today.']);
    }

    /** @test */
    public function non_authenticated_user_cannot_verifiy_the_identity_of_an_user()
    {
        $this->json('post','/api/admin/users/100/verify-identity')
            ->assertUnauthorized();
    }

    /** @test */
    public function non_compliance_user_cannon_verify_the_identity_of_an_user()
    {
        $user = User::factory()->create();
        $user->assignRole(['base', 'admin', 'super_admin', 'agent', 'user']);
        Sanctum::actingAs($user);

        $client = User::factory()->create();
        $client->assignRole('user');
        Identity::factory()->create([
            'user_id'   => $client->id,
        ]);

        $this->json('post', '/api/admin/users/' . $client->id . '/verify-identity')
            ->assertForbidden();
    }

    /** @test */
    public function an_user_with_compliance_role_can_verify_the_identity_of_an_user()
    {
        $user = User::factory()->create();
        $user->assignRole(['compliance']);
        Sanctum::actingAs($user);

        $client = User::factory()->create();
        $client->assignRole('user');
        Identity::factory()->create([
            'user_id'   => $client->id,
        ]);

        $this->json('post', '/api/admin/users/' . $client->id . '/verify-identity')
            ->assertOk();

        $client->refresh();
        $this->assertNotNull($client->identity->verified_at);
        $this->assertTrue($client->identity->isVerified);
        $this->assertNull($client->identity->rejected_at);
        $this->assertFalse($client->identity->isrejected);
    }

    /** @test */
    public function cannot_verify_non_existing_identity_of_an_user()
    {
        $user = User::factory()->create();
        $user->assignRole(['compliance']);
        Sanctum::actingAs($user);

        $this->json('post', '/api/admin/users/100/verify-identity')
            ->assertNotFound();
    }

    /** @test */
    public function cannot_verify_an_identity_of_an_user_with_non_user_role()
    {
        $user = User::factory()->create();
        $user->assignRole(['compliance']);
        Sanctum::actingAs($user);

        $client = User::factory()->create();
        $client->assignRole(['base', 'admin', 'super_admin', 'agent']);


        $this->json('post', '/api/admin/users/' . $client->id . '/verify-identity')
            ->assertForbidden();
    }

    /** @test */
    public function cannot_verify_an_identity_to_an_user_with_non_identity_created()
    {
        $user = User::factory()->create();
        $user->assignRole(['compliance']);
        Sanctum::actingAs($user);

        $client = User::factory()->create();
        $client->assignRole(['user']);


        $this->json('post', '/api/admin/users/' . $client->id . '/verify-identity')
            ->assertForbidden();
    }

    /** @test */
    public function cannot_verify_an_indetity_if_is_already_verified()
    {
        $user = User::factory()->create();
        $user->assignRole(['compliance']);
        Sanctum::actingAs($user);

        $client = User::factory()->create();
        $client->assignRole(['user']);
        Identity::factory()->create([
            'user_id'       => $client->id,
            'verified_at'   => now()
        ]);

        $this->json('post', '/api/admin/users/' . $client->id . '/verify-identity')
            ->assertForbidden();
    }

    /** @test */
    public function cannot_verify_an_identity_if_is_already_rejected()
    {
        $user = User::factory()->create();
        $user->assignRole(['compliance']);
        Sanctum::actingAs($user);

        $client = User::factory()->create();
        $client->assignRole(['user']);
        Identity::factory()->create([
            'user_id'       => $client->id,
            'rejected_at'   => now()
        ]);

        $this->json('post', '/api/admin/users/' . $client->id . '/verify-identity')
            ->assertForbidden();
    }

    /** @test */
    public function non_authenticated_user_cannot_reject_the_identity_of_an_user()
    {
        $this->json('post','/api/admin/users/100/reject-identity')
            ->assertUnauthorized();
    }

    /** @test */
    public function non_compliance_user_cannot_reject_the_identity_of_an_user()
    {
        $user = User::factory()->create();
        $user->assignRole(['base', 'admin', 'super_admin', 'agent', 'user']);
        Sanctum::actingAs($user);

        $client = User::factory()->create();
        $client->assignRole('user');
        Identity::factory()->create([
            'user_id'   => $client->id,
        ]);

        $this->json('post', '/api/admin/users/' . $client->id . '/reject-identity')
            ->assertForbidden();
    }

    /** @test */
    public function a_compliance_user_can_reject_the_identity_of_an_user()
    {
        $user = User::factory()->create();
        $user->assignRole(['compliance']);
        Sanctum::actingAs($user);

        $client = User::factory()->create();
        $client->assignRole('user');
        Identity::factory()->create([
            'user_id'   => $client->id,
        ]);

        $this->json('post', '/api/admin/users/' . $client->id . '/reject-identity', ['rejection_reasons' => 'reasons'])
            ->assertOk();

        $client->refresh();
        $this->assertNull($client->identity->verified_at);
        $this->assertFalse($client->identity->isVerified);
        $this->assertNotNull($client->identity->rejected_at);
        $this->assertTrue($client->identity->isrejected);
    }

    /** @test */
    public function rejection_reasons_is_required_to_reject_an_identity()
    {
        $user = User::factory()->create();
        $user->assignRole(['compliance']);
        Sanctum::actingAs($user);

        $client = User::factory()->create();
        $client->assignRole('user');
        Identity::factory()->create([
            'user_id'   => $client->id,
        ]);

        $this->json('post', '/api/admin/users/' . $client->id . '/reject-identity', ['rejection_reasons' => null])
            ->assertStatus(422)
            ->assertJsonFragment(['The rejection reasons field is required.']);
    }

    /** @test */
    public function cannot_reject_a_non_existing_identity_of_a_user()
    {
        $user = User::factory()->create();
        $user->assignRole(['compliance']);
        Sanctum::actingAs($user);

        $this->json('post', '/api/admin/users/100/reject-identity')
            ->assertNotFound();
    }

    /** @test */
    public function cannot_reject_an_identity_of_an_user_with_non_user_role()
    {
        $user = User::factory()->create();
        $user->assignRole(['compliance']);
        Sanctum::actingAs($user);

        $client = User::factory()->create();
        $client->assignRole(['base', 'admin', 'super_admin', 'agent']);


        $this->json('post', '/api/admin/users/' . $client->id . '/reject-identity')
            ->assertForbidden();
    }

    /** @test */
    public function cannot_reject_an_identity_to_an_user_with_non_identity_created()
    {
        $user = User::factory()->create();
        $user->assignRole(['compliance']);
        Sanctum::actingAs($user);

        $client = User::factory()->create();
        $client->assignRole(['user']);

        $this->json('post', '/api/admin/users/' . $client->id . '/reject-identity')
            ->assertForbidden();
    }

    /** @test */
    public function cannot_reject_an_indetity_if_is_already_verified()
    {
        $user = User::factory()->create();
        $user->assignRole(['compliance']);
        Sanctum::actingAs($user);

        $client = User::factory()->create();
        $client->assignRole(['user']);
        Identity::factory()->create([
            'user_id'       => $client->id,
            'verified_at'   => now()
        ]);

        $this->json('post', '/api/admin/users/' . $client->id . '/reject-identity')
            ->assertForbidden();
    }

    /** @test */
    public function cannot_reject_an_identity_if_is_already_rejected()
    {
        $user = User::factory()->create();
        $user->assignRole(['compliance']);
        Sanctum::actingAs($user);

        $client = User::factory()->create();
        $client->assignRole(['user']);
        Identity::factory()->create([
            'user_id'       => $client->id,
            'rejected_at'   => now()
        ]);

        $this->json('post', '/api/admin/users/' . $client->id . '/reject-identity')
            ->assertForbidden();
    }
}
