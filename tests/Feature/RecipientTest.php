<?php

namespace Tests\Feature;

use App\Models\Recipient;
use Tests\TestCase;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Sanctum\Sanctum;

class RecipientTest extends TestCase
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
    public function non_authenticated_user_cannot_view_index_of_recipients()
    {
        $this->json('get', 'api/recipients')
            ->assertUnauthorized();
    }

    /** @test */
    public function non_administrative_users_cannot_view_index_of_recipients_for_user()
    {
        $user = User::factory()->create();
        $user->assignRole(['base', 'admin', 'super_admin', 'compliance', 'agent']);
        Sanctum::actingAs($user);

        $this->json('get', 'api/recipients')
            ->assertForbidden();
    }

    /** @test */
    public function a_regular_user_can_view_index_of_his_recipients()
    {
        $user = User::factory()->create();
        $user->assignRole(['user']);
        Sanctum::actingAs($user);

        Recipient::factory()->count(10)->create([
            'user_id' => $user->id
        ]);
        Recipient::factory()->count(10)->create();

        $this->json('get', 'api/recipients')
            ->assertOk()
            ->assertJsonCount(10, 'data')
            ->assertJsonStructure([
                'data' => [
                    [
                        'id',
                        'user_id',
                        'bank' => [],
                        'country' => [],
                        'name',
                        'lastname',
                        'dni',
                        'phone',
                        'email',
                        'bank_name',
                        'bank_account',
                        'account_type',
                        'bank_code',
                        'address',
                        'created_at',
                        'updated_at',
                    ]
                ]
            ]);
    }

    /** @test */
    public function non_authenticated_user_cannot_view_a_single_recipient()
    {
        $this->json('get', 'api/recipients/100')
            ->assertUnauthorized();
    }

    /** @test */
    public function cannot_view_a_recipient_that_doesnt_exists()
    {
        $user = User::factory()->create();
        $user->assignRole(['compliance']);
        Sanctum::actingAs($user);

        $this->json('get', 'api/recipients/100')
            ->assertNotFound();
    }

    /** @test */
    public function administrative_users_cannot_view_single_recipient_except_compliance_users()
    {
        $user = User::factory()->create();
        $user->assignRole(['base', 'admin', 'super_admin', 'agent']);
        Sanctum::actingAs($user);

        $recipient = Recipient::factory()->create();

        $this->json('get', 'api/recipients/' . $recipient->id)
            ->assertForbidden();
    }

    /** @test */
    public function a_compliance_user_can_view_a_single_recipient()
    {
        $user = User::factory()->create();
        $user->assignRole(['base', 'compliance']);
        Sanctum::actingAs($user);

        $recipient = Recipient::factory()->create();

        $this->json('get', 'api/recipients/' . $recipient->id)
            ->assertOk()
            ->assertJsonFragment([ 'id' => $recipient->id])
            ->assertJsonStructure([
                    'data' => [
                        'id',
                        'user_id',
                        'bank' => [],
                        'country' => [],
                        'name',
                        'lastname',
                        'dni',
                        'phone',
                        'email',
                        'bank_name',
                        'bank_account',
                        'account_type',
                        'bank_code',
                        'address',
                        'created_at',
                        'updated_at',
                    ]
                ]);
    }

    /** @test */
    public function a_user_with_user_role_can_view_a_single_user()
    {
        $user = User::factory()->create();
        $user->assignRole(['base', 'user']);
        Sanctum::actingAs($user);

        $recipient = Recipient::factory()->create([
            'user_id' => $user->id
        ]);

        $this->json('get', 'api/recipients/' . $recipient->id)
            ->assertOk()
            ->assertJsonFragment([ 'id' => $recipient->id])
            ->assertJsonStructure([
                    'data' => [
                        'id',
                        'user_id',
                        'bank' => [],
                        'country' => [],
                        'name',
                        'lastname',
                        'dni',
                        'phone',
                        'email',
                        'bank_name',
                        'bank_account',
                        'account_type',
                        'bank_code',
                        'address',
                        'created_at',
                        'updated_at',
                    ]
                ]);
    }

    /** @test */
    public function a_regular_user_cannot_view_a_single_user_if_this_doesnt_belongs_to_him()
    {
        $user = User::factory()->create();
        $user->assignRole(['base', 'user']);
        Sanctum::actingAs($user);

        $recipient = Recipient::factory()->create();

        $this->json('get', 'api/recipients/' . $recipient->id)
            ->assertForbidden();
    }

    /** @test */
    public function non_authenticated_user_cannot_create_a_recipient()
    {
        $this->json('post', 'api/recipients')
            ->assertUnauthorized();
    }

    /** @test */
    public function administrative_users_cannot_create_a_recipient()
    {
        $user = User::factory()->create();
        $user->assignRole(['base', 'admin', 'super_admin', 'compliance', 'agent']);
        Sanctum::actingAs($user);

        $this->json('post', 'api/recipients')
            ->assertForbidden();
    }

    /** @test */
    public function a_regular_user_can_create_a_recipient()
    {
        $user = User::factory()->create();
        $user->assignRole(['user']);
        Sanctum::actingAs($user);

        $recipient = Recipient::factory()->raw([
            'user_id' => null,
        ]);

        $this->json('post', 'api/recipients', $recipient)
            ->assertCreated();

        $this->assertDatabaseHas('recipients', [
            'user_id'       => $user->id,
            'name'          => $recipient['name'],
            'lastname'      => $recipient['lastname'],
            'dni'           => $recipient['dni'],
            'country_id'    => $recipient['country_id'],
            'bank_id'       => $recipient['bank_id'],
            'phone'         => $recipient['phone'],
            'email'         => $recipient['email'],
            'bank_name'     => $recipient['bank_name'],
            'bank_account'  => $recipient['bank_account'],
            'account_type'  => $recipient['account_type'],
            'bank_code'     => $recipient['bank_code'],
            'address'       => $recipient['address'],
        ]);
    }

    /** @test */
    public function validate_the_required_fiels_to_create_a_recipient()
    {
        $user = User::factory()->create();
        $user->assignRole(['user']);
        Sanctum::actingAs($user);

        $recipient = Recipient::factory()->raw([
            'user_id'       => null,
            'name'          => null,
            'lastname'      => null,
            'country_id'    => null,
            'dni'           => null,
            'bank_id'       => null,
        ]);

        $this->json('post', 'api/recipients', $recipient)
            ->assertStatus(422)
            ->assertJsonFragment(['The name field is required.'])
            ->assertJsonFragment(['The lastname field is required.'])
            ->assertJsonFragment(['The country id field is required.'])
            ->assertJsonFragment(['The bank id field is required.'])
            ->assertJsonFragment(['The dni field is required.']);
    }

    /** @test */
    public function the_country_must_exists_to_create_a_recipient()
    {
        $user = User::factory()->create();
        $user->assignRole(['user']);
        Sanctum::actingAs($user);

        $recipient = Recipient::factory()->raw([
            'user_id'       => null,
            'country_id'    => 100,
        ]);

        $this->json('post', 'api/recipients', $recipient)
            ->assertStatus(422)
            ->assertJsonFragment(['The selected country id is invalid.']);
    }

    /** @test */
    public function the_bank_must_exists_to_create_a_recipient()
    {
        $user = User::factory()->create();
        $user->assignRole(['user']);
        Sanctum::actingAs($user);

        $recipient = Recipient::factory()->raw([
            'user_id'       => null,
            'bank_id'       => 100,
        ]);

        $this->json('post', 'api/recipients', $recipient)
            ->assertStatus(422)
            ->assertJsonFragment(['The selected bank id is invalid.']);
    }

    /** @test */
    public function the_email_field_must_have_mail_format()
    {
        $user = User::factory()->create();
        $user->assignRole(['user']);
        Sanctum::actingAs($user);

        $recipient = Recipient::factory()->raw([
            'user_id'       => null,
            'email'         => 'not an email',
        ]);

        $this->json('post', 'api/recipients', $recipient)
            ->assertStatus(422)
            ->assertJsonFragment(['The email must be a valid email address.']);
    }

    /** @test */
    public function non_authenticated_user_cannot_update_a_recipient()
    {
        $this->json('put', 'api/recipients/100')
            ->assertUnauthorized();
    }

    /** @test */
    public function cannot_update_non_existing_recipient()
    {
        $user = User::factory()->create();
        $user->assignRole(['user']);
        Sanctum::actingAs($user);

        $this->json('put', 'api/recipients/100')
            ->assertNotFound();
    }

    /** @test */
    public function an_administrative_user_cannot_update_a_recipient()
    {
        $user = User::factory()->create();
        $user->assignRole(['base', 'admin', 'super_admin', 'compliance', 'agent']);
        Sanctum::actingAs($user);

        $recipient = Recipient::factory()->create();

        $this->json('put', 'api/recipients/' . $recipient->id)
            ->assertForbidden();
    }

    /** @test */
    public function a_regular_user_can_update_a_recipient()
    {
        $user = User::factory()->create();
        $user->assignRole(['user']);
        Sanctum::actingAs($user);

        $recipient = Recipient::factory()->create([
            'user_id' => $user->id
        ]);

        $this->json('put', 'api/recipients/' . $recipient->id, [
            'phone'         => '+12345678',
            'email'         => 'newmail@email.com',
            'address'       => 'new adderss',
        ])->assertOk();

        $this->assertDatabaseHas('recipients', [
            'id'        => $recipient->id,
            'phone'     => '+12345678',
            'email'     => 'newmail@email.com',
            'address'   => 'new adderss',
        ]);
    }

    /** @test */
    public function the_email_must_be_valid_to_updated_a_recipient()
    {
        $user = User::factory()->create();
        $user->assignRole(['user']);
        Sanctum::actingAs($user);

        $recipient = Recipient::factory()->create([
            'user_id' => $user->id
        ]);

        $this->json('put', 'api/recipients/' . $recipient->id, [
            'email'         => 'not an email',
        ])->assertStatus(422)
            ->assertJsonFragment(['The email must be a valid email address.']);
    }

    /** @test */
    public function cannot_update_a_recipient_if_this_not_belongs()
    {
        $user = User::factory()->create();
        $user->assignRole(['user']);
        Sanctum::actingAs($user);

        $recipient = Recipient::factory()->create();

        $this->json('put', 'api/recipients/' . $recipient->id, [])
            ->assertForbidden();
    }
}
