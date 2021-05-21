<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Remitter;
use Laravel\Sanctum\Sanctum;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class RemitterTest extends TestCase
{
    use DatabaseMigrations, RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    /** @test */
    public function non_authenticated_user_cannot_create_a_remitter()
    {
        $this->json('post', '/api/remitters')
            ->assertUnauthorized();
    }

    /** @test */
    public function non_agent_user_cannot_create_remitter()
    {
        $user = User::factory()->create();
        $user->assignRole(['user', 'base', 'admin', 'super_admin', 'compliance']);
        Sanctum::actingAs($user);

        $this->json('post', '/api/remitters')
            ->assertForbidden();
    }

    /** @test */
    public function agent_user_can_create_a_remitter()
    {
        $user = User::factory()->create();
        $user->assignRole(['agent']);
        Sanctum::actingAs($user);

        $remitter = Remitter::factory()->raw();

        $this->json('post', '/api/remitters', $remitter)
            ->assertCreated();

        $this->assertDatabaseHas('remitters', [
            'fullname' => $remitter['fullname'],
            'user_id' => $user->id,
            'document_type' => $remitter['document_type'],
            'dni' => $remitter['dni'],
            'issuance_date' => $remitter['issuance_date'],
            'expiration_date' => $remitter['expiration_date'],
            'address' => $remitter['address'],
            'city' => $remitter['city'],
            'state' => $remitter['state'],
            'country_id' => $remitter['country_id'],
            'phone' => $remitter['phone'],
            'email' => $remitter['email'],
        ]);
    }

    /** @test */
    public function cannot_create_a_remitter_without_required_fields()
    {
        $user = User::factory()->create();
        $user->assignRole(['agent']);
        Sanctum::actingAs($user);

        $remitter = Remitter::factory()->raw([
            'fullname' => null,
            'document_type' => null,
            'dni' => null,
            'country_id' => null,
            'issuance_date' => null,
            'expiration_date' => null,
            'dob' => null,
            'issuance_country_id' => null,
            'phone' => null,
        ]);

        $this->json('post', '/api/remitters', $remitter)
            ->assertStatus(422)
            ->assertJsonFragment(['The fullname field is required.'])
            ->assertJsonFragment(['The document type field is required.'])
            ->assertJsonFragment(['The dni field is required.'])
            ->assertJsonFragment(['The country id field is required.'])
            ->assertJsonFragment(['The issuance date field is required.'])
            ->assertJsonFragment(['The expiration date field is required.'])
            ->assertJsonFragment(['The dob field is required.'])
            ->assertJsonFragment(['The issuance country id field is required.'])
            ->assertJsonFragment(['The phone field is required.']);
    }

    /** @test */
    public function non_authenticated_user_cannot_view_index_of_remitters()
    {
        $this->json('get', '/api/remitters')
            ->assertUnauthorized();
    }

    /** @test */
    public function non_agent_user_cannot_view_index_of_remitters()
    {
        $user = User::factory()->create();
        $user->assignRole(['user', 'base', 'admin', 'super_admin', 'compliance']);
        Sanctum::actingAs($user);

        $this->json('get', '/api/remitters')
            ->assertForbidden();
    }

    /** @test */
    public function agent_user_can_view_index_of_remitters_and_view_only_their_remitters()
    {
        $user = User::factory()->create();
        $user->assignRole(['agent']);
        Sanctum::actingAs($user);

        Remitter::factory()->count(10)->create([
            'user_id' => $user->id
        ]);
        Remitter::factory()->count(5)->create();

        $this->json('get', '/api/remitters')
            ->assertOk()
            ->assertJsonCount(10, 'data')
            ->assertJsonStructure([
                'data' => [
                    [
                        'id',
                        'user_id',
                        'fullname',
                        'document_type',
                        'dni',
                        'issuance_date',
                        'expiration_date',
                        'dob',
                        'address',
                        'city',
                        'state',
                        'country_id',
                        'issuance_country_id',
                        'phone',
                        'email',
                        'document_url',
                        'reverse_url',
                        'created_at',
                        'updated_at'
                    ]
                ],
                'links' => [],
                'meta' => []
            ]);
    }

    /** @test */
    public function agent_user_can_view_index_of_remitters_using_querys_without_pagination()
    {
        $user = User::factory()->create();
        $user->assignRole(['agent']);
        Sanctum::actingAs($user);

        Remitter::factory()->count(10)->create([
            'user_id' => $user->id
        ]);

        Remitter::factory()->create([
            'user_id' => $user->id,
            'fullname' => 'Jhon Doe Custom Name'
        ]);
        Remitter::factory()->create([
            'user_id' => $user->id,
            'fullname' => 'Jane Doe cusTOM Name'
        ]);

        $this->json('get', '/api/remitters?fullname=custom')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure([
                'data' => [
                    [
                        'id',
                        'user_id',
                        'fullname',
                        'document_type',
                        'dni',
                        'issuance_date',
                        'expiration_date',
                        'dob',
                        'address',
                        'city',
                        'state',
                        'country_id',
                        'issuance_country_id',
                        'phone',
                        'email',
                        'document_url',
                        'reverse_url',
                        'created_at',
                        'updated_at'
                    ]
                ],
            ]);
    }

    /** @test */
    public function non_authenticated_user_cannot_view_single_remitter()
    {
        $this->json('get', '/api/remitters/100')
            ->assertUnauthorized();
    }

    /** @test */
    public function cannot_view_non_existing_remitter()
    {
        $user = User::factory()->create();
        $user->assignRole(['user', 'base', 'admin', 'super_admin', 'compliance']);
        Sanctum::actingAs($user);

        $this->json('get', '/api/remitters/100')
            ->assertNotFound();
    }

    /** @test */
    public function non_agent_user_cannot_view_a_single_remitter()
    {
        $user = User::factory()->create();
        $user->assignRole(['user', 'base', 'admin', 'super_admin', 'compliance']);
        Sanctum::actingAs($user);

        $remitter = Remitter::factory()->create();

        $this->json('get', '/api/remitters/' . $remitter->id)
            ->assertForbidden();
    }

    /** @test */
    public function an_agent_user_cannot_view_a_single_user_that_does_not_belongs()
    {
        $user = User::factory()->create();
        $user->assignRole(['agent']);
        Sanctum::actingAs($user);

        $remitter = Remitter::factory()->create();

        $this->json('get', '/api/remitters/' . $remitter->id)
            ->assertForbidden();
    }

    /** @test */
    public function an_agent_user_can_view_a_single_user()
    {
        $user = User::factory()->create();
        $user->assignRole(['agent']);
        Sanctum::actingAs($user);

        $remitter = Remitter::factory()->create([
            'user_id' => $user->id
        ]);

        $this->json('get', '/api/remitters/' . $remitter->id)
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'user_id',
                    'fullname',
                    'document_type',
                    'dni',
                    'issuance_date',
                    'expiration_date',
                    'dob',
                    'address',
                    'city',
                    'state',
                    'country_id',
                    'issuance_country_id',
                    'phone',
                    'email',
                    'document_url',
                    'reverse_url',
                    'created_at',
                    'updated_at'
                ],
            ]);
    }

    /** @test */
    public function non_authenticated_user_cannot_update_an_remitter()
    {
        $this->json('put', '/api/remitters/100')
            ->assertUnauthorized();
    }

    /** @test */
    public function cannot_update_non_existing_remitter()
    {
        $user = User::factory()->create();
        $user->assignRole(['user', 'base', 'admin', 'super_admin', 'compliance']);
        Sanctum::actingAs($user);

        $this->json('put', '/api/remitters/100')
            ->assertNotFound();
    }

    /** @test */
    public function non_agent_user_cannot_update_a_remitter()
    {
        $user = User::factory()->create();
        $user->assignRole(['user', 'base', 'admin', 'super_admin', 'compliance']);
        Sanctum::actingAs($user);

        $remitter = Remitter::factory()->create();

        $this->json('get', '/api/remitters/' . $remitter->id)
            ->assertForbidden();
    }

    /** @test */
    public function agent_user_cannot_update_a_remitter_that_doesnt_not_belongs()
    {
        $user = User::factory()->create();
        $user->assignRole(['agent']);
        Sanctum::actingAs($user);

        $remitter = Remitter::factory()->create();

        $this->json('get', '/api/remitters/' . $remitter->id, [
            'fullname' => 'Jhon Doe',
            'document_type' => 'CC',
            'dni' => '1234567890',
            'issuance_date' => '2019-01-01',
            'expiration_date' => '2023-01-01',
            'dob' => '2001-01-01',
            'address' => 'fake avenue #0001',
            'city' => 'city',
            'state' => 'state',
            'country_id' => 1,
            'issuance_country_id' => 1,
            'phone' => '6218041089231',
            'email' => 'jhon@test.com',
        ])->assertForbidden();
    }

    /** @test */
    public function agent_can_update_a_remitter()
    {
        $user = User::factory()->create();
        $user->assignRole(['agent']);
        Sanctum::actingAs($user);

        $remitter = Remitter::factory()->create([
            'user_id' => $user->id
        ]);

        $this->json('get', '/api/remitters/' . $remitter->id, [
            'fullname' => 'Jhon Doe',
            'document_type' => 'CC',
            'dni' => '1234567890',
            'issuance_date' => '2019-01-01',
            'expiration_date' => '2023-01-01',
            'dob' => '2001-01-01',
            'address' => 'fake avenue #0001',
            'city' => 'city',
            'state' => 'state',
            'country_id' => 1,
            'issuance_country_id' => 1,
            'phone' => '6218041089231',
            'email' => 'jhon@test.com',
        ])->assertOk();

        $this->assertDatabaseMissing('remitters', [
            'id' => $remitter->id,
            'fullname' => 'Jhon Doe',
            'document_type' => 'CC',
            'dni' => '1234567890',
            'issuance_date' => '2019-01-01',
            'expiration_date' => '2023-01-01',
            'dob' => '2001-01-01',
            'address' => 'fake avenue #0001',
            'city' => 'city',
            'state' => 'state',
            'country_id' => 1,
            'issuance_country_id' => 1,
            'phone' => '6218041089231',
            'email' => 'jhon@test.com',
        ]);
    }

    /** @test */
    public function non_authenticated_user_cannot_delete_a_remitter()
    {
        $this->json('delete', '/api/remitters/100')
            ->assertUnauthorized();
    }

    /** @test */
    public function cannot_delete_non_existing_remitter()
    {
        $user = User::factory()->create();
        $user->assignRole(['user', 'base', 'admin', 'super_admin', 'compliance']);
        Sanctum::actingAs($user);

        $this->json('delete', '/api/remitters/100')
            ->assertNotFound();
    }

    /** @test */
    public function non_agent_user_cannot_delete_a_remitter()
    {
        $user = User::factory()->create();
        $user->assignRole(['user', 'base', 'admin', 'super_admin', 'compliance']);
        Sanctum::actingAs($user);

        $remitter = Remitter::factory()->create();

        $this->json('delete', '/api/remitters/' . $remitter->id)
            ->assertForbidden();
    }

    /** @test */
    public function agent_cannot_delete_a_remitter_if_does_not_belongs_to_it()
    {
        $user = User::factory()->create();
        $user->assignRole(['agent']);
        Sanctum::actingAs($user);

        $remitter = Remitter::factory()->create();

        $this->json('delete', '/api/remitters/' . $remitter->id)
            ->assertForbidden();

        $this->assertDatabaseHas('remitters', ['id' => $remitter->id]);
    }

    /** @test */
    public function agent_can_delete_a_remitter()
    {
        $user = User::factory()->create();
        $user->assignRole(['agent']);
        Sanctum::actingAs($user);

        $remitter = Remitter::factory()->create([
            'user_id' => $user->id
        ]);

        $this->json('delete', '/api/remitters/' . $remitter->id)
            ->assertOk();

        $this->assertDatabaseMissing('remitters', ['id' => $remitter->id]);
    }
}
