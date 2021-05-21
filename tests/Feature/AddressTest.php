<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Country;
use Tests\TestCase;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class AddressTest extends TestCase
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
    public function non_autehnticated_user_cannot_create_an_address()
    {
        $this->json('post', 'api/users/address')
            ->assertUnauthorized();
    }

    /** @test */
    public function an_authenticated_user_can_create_an_identity()
    {
        $user = User::factory()->create();
        $user->assignRole('user');
        Sanctum::actingAs($user);

        $country = Country::factory()->create();
        $address = Address::factory()->raw([
            'user_id'       => null,
            'coountry_id'   => $country->id
        ]);

        $this->json('post', 'api/users/address', $address)
            ->assertCreated();

        $this->assertDatabaseHas('addresses', [
            'user_id'           => $user->id,
            'address'           => $address['address'],
            'address_ext'       => $address['address_ext'],
            'state'             => $address['state'],
            'city'              => $address['city'],
            'cod'               => $address['cod'],
            'verified_at'       => null,
            'rejected_at'       => null,
        ]);
    }

    /** @test */
    public function an_user_cannot_create_an_address_if_it_has_already_created_one()
    {
        $user = User::factory()->create();
        $user->assignRole('user');
        Sanctum::actingAs($user);

        Address::factory()->create([
            'user_id'       => $user->id,
            'verified_at'   => null,
            'rejected_at'   => null
        ]);

        $address = Address::factory()->raw([
            'user_id'       => null
        ]);

        $this->json('post', 'api/users/address', $address)
            ->assertForbidden();
    }

    /** @test */
    public function an_user_can_create_an_address_if_it_has_already_created_one_and_its_rejected()
    {
        $user = User::factory()->create();
        $user->assignRole('user');
        Sanctum::actingAs($user);

        Address::factory()->create([
            'user_id'       => $user->id,
            'verified_at'   => null,
            'rejected_at'   => now()
        ]);

        $address = Address::factory()->raw([
            'user_id'       => null
        ]);

        $this->json('post', 'api/users/address', $address)
            ->assertCreated();
    }

    /** @test */
    public function an_admin_user_cannot_create_an_address()
    {
        $user = User::factory()->create();
        $user->assignRole(['base', 'admin', 'super_admin', 'agent', 'compliance']);
        Sanctum::actingAs($user);

        $address = Address::factory()->raw([
            'user_id'   => null,
        ]);

        $this->json('post', 'api/users/address', $address)
            ->assertForbidden();
    }

    /** @test */
    public function cannot_create_an_address_without_required_fields()
    {
        $user = User::factory()->create();
        $user->assignRole('user');
        Sanctum::actingAs($user);

        $address = Address::factory()->raw([
            'user_id'           => null,
            'country_id'        => null,
            'address'           => null,
            'state'             => null,
            'cod'               => null,
        ]);

        $this->json('post', 'api/users/address', $address)
            ->assertStatus(422)
            ->assertJsonFragment(['The country id field is required.'])
            ->assertJsonFragment(['The address field is required.'])
            ->assertJsonFragment(['The state field is required.'])
            ->assertJsonFragment(['The cod field is required.']);
    }

    /** @test */
    public function country_must_exists()
    {
        $user = User::factory()->create();
        $user->assignRole('user');
        Sanctum::actingAs($user);

        $address = Address::factory()->raw([
            'country_id'        => 100,
        ]);

        $this->json('post', 'api/users/address', $address)
            ->assertStatus(422)
            ->assertJsonFragment(['The selected country id is invalid.']);
    }

    /** @test */
    public function non_authenticated_user_cannot_verifiy_the_address_of_an_user()
    {
        $this->json('post','/api/admin/users/100/verify-address')
            ->assertUnauthorized();
    }

    /** @test */
    public function non_compliance_user_cannon_verify_the_address_of_an_user()
    {
        $user = User::factory()->create();
        $user->assignRole(['base', 'admin', 'super_admin', 'agent', 'user']);
        Sanctum::actingAs($user);

        $client = User::factory()->create();
        $client->assignRole('user');
        Address::factory()->create([
            'user_id'   => $client->id,
        ]);

        $this->json('post', '/api/admin/users/' . $client->id . '/verify-address')
            ->assertForbidden();
    }

    /** @test */
    public function an_user_with_compliance_role_can_verify_the_address_of_an_user()
    {
        $user = User::factory()->create();
        $user->assignRole(['compliance']);
        Sanctum::actingAs($user);

        $client = User::factory()->create();
        $client->assignRole('user');
        Address::factory()->create([
            'user_id'   => $client->id,
        ]);

        $this->json('post', '/api/admin/users/' . $client->id . '/verify-address')
            ->assertOk();

        $client->refresh();
        $this->assertNotNull($client->address->verified_at);
        $this->assertTrue($client->address->isVerified);
        $this->assertNull($client->address->rejected_at);
        $this->assertFalse($client->address->isrejected);
    }

    /** @test */
    public function cannot_verify_non_existing_address_of_an_user()
    {
        $user = User::factory()->create();
        $user->assignRole(['compliance']);
        Sanctum::actingAs($user);

        $this->json('post', '/api/admin/users/100/verify-address')
            ->assertNotFound();
    }

    /** @test */
    public function cannot_verify_an_address_of_an_user_with_non_user_role()
    {
        $user = User::factory()->create();
        $user->assignRole(['compliance']);
        Sanctum::actingAs($user);

        $client = User::factory()->create();
        $client->assignRole(['base', 'admin', 'super_admin', 'agent']);


        $this->json('post', '/api/admin/users/' . $client->id . '/verify-address')
            ->assertForbidden();
    }

    /** @test */
    public function cannot_verify_an_address_to_an_user_with_non_address_created()
    {
        $user = User::factory()->create();
        $user->assignRole(['compliance']);
        Sanctum::actingAs($user);

        $client = User::factory()->create();
        $client->assignRole(['user']);

        $this->json('post', '/api/admin/users/' . $client->id . '/verify-address')
            ->assertForbidden();
    }

    /** @test */
    public function cannot_verify_an_address_if_is_already_verified()
    {
        $user = User::factory()->create();
        $user->assignRole(['compliance']);
        Sanctum::actingAs($user);

        $client = User::factory()->create();
        $client->assignRole(['user']);
        Address::factory()->create([
            'user_id'       => $client->id,
            'verified_at'   => now()
        ]);

        $this->json('post', '/api/admin/users/' . $client->id . '/verify-address')
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
        Address::factory()->create([
            'user_id'       => $client->id,
            'rejected_at'   => now()
        ]);

        $this->json('post', '/api/admin/users/' . $client->id . '/verify-address')
            ->assertForbidden();
    }

    /** @test */
    public function non_authenticated_user_cannot_reject_the_address_of_an_user()
    {
        $this->json('post','/api/admin/users/100/reject-address')
            ->assertUnauthorized();
    }

    /** @test */
    public function non_compliance_user_cannot_reject_the_address_of_an_user()
    {
        $user = User::factory()->create();
        $user->assignRole(['base', 'admin', 'super_admin', 'agent', 'user']);
        Sanctum::actingAs($user);

        $client = User::factory()->create();
        $client->assignRole('user');
        Address::factory()->create([
            'user_id'   => $client->id,
        ]);

        $this->json('post', '/api/admin/users/' . $client->id . '/reject-address')
            ->assertForbidden();
    }

    /** @test */
    public function a_compliance_user_can_reject_the_address_of_an_user()
    {
        $user = User::factory()->create();
        $user->assignRole(['compliance']);
        Sanctum::actingAs($user);

        $client = User::factory()->create();
        $client->assignRole('user');
        Address::factory()->create([
            'user_id'   => $client->id,
        ]);

        $this->json('post', '/api/admin/users/' . $client->id . '/reject-address')
            ->assertOk();

        $client->refresh();
        $this->assertNull($client->address->verified_at);
        $this->assertFalse($client->address->isVerified);
        $this->assertNotNull($client->address->rejected_at);
        $this->assertTrue($client->address->isrejected);
    }

    /** @test */
    public function cannot_reject_a_non_existing_address_of_a_user()
    {
        $user = User::factory()->create();
        $user->assignRole(['compliance']);
        Sanctum::actingAs($user);

        $this->json('post', '/api/admin/users/100/reject-address')
            ->assertNotFound();
    }

    /** @test */
    public function cannot_reject_an_address_of_an_user_with_non_user_role()
    {
        $user = User::factory()->create();
        $user->assignRole(['compliance']);
        Sanctum::actingAs($user);

        $client = User::factory()->create();
        $client->assignRole(['base', 'admin', 'super_admin', 'agent']);


        $this->json('post', '/api/admin/users/' . $client->id . '/reject-address')
            ->assertForbidden();
    }

    /** @test */
    public function cannot_reject_an_address_to_an_user_with_non_address_created()
    {
        $user = User::factory()->create();
        $user->assignRole(['compliance']);
        Sanctum::actingAs($user);

        $client = User::factory()->create();
        $client->assignRole(['user']);

        $this->json('post', '/api/admin/users/' . $client->id . '/reject-address')
            ->assertForbidden();
    }

    /** @test */
    public function cannot_reject_an_address_if_is_already_verified()
    {
        $user = User::factory()->create();
        $user->assignRole(['compliance']);
        Sanctum::actingAs($user);

        $client = User::factory()->create();
        $client->assignRole(['user']);
        Address::factory()->create([
            'user_id'       => $client->id,
            'verified_at'   => now()
        ]);

        $this->json('post', '/api/admin/users/' . $client->id . '/reject-address')
            ->assertForbidden();
    }

    /** @test */
    public function cannot_reject_an_address_if_is_already_rejected()
    {
        $user = User::factory()->create();
        $user->assignRole(['compliance']);
        Sanctum::actingAs($user);

        $client = User::factory()->create();
        $client->assignRole(['user']);
        Address::factory()->create([
            'user_id'       => $client->id,
            'rejected_at'   => now()
        ]);

        $this->json('post', '/api/admin/users/' . $client->id . '/reject-address')
            ->assertForbidden();
    }
}
