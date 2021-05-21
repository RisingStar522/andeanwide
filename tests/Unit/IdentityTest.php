<?php

namespace Tests\Unit;

use App\Models\Country;
use Tests\TestCase;
use App\Models\User;
use App\Models\Identity;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class IdentityTest extends TestCase
{
    use DatabaseMigrations, RefreshDatabase, WithFaker;

    /** @test */
    public function can_create_a_identity()
    {
        Identity::create([
            'user_id'                   => 1,
            'identity_number'           => '123456789',
            'document_type'             => 'dni',
            'firstname'                 => 'Jhon',
            'lastname'                  => 'Doe',
            'dob'                       => now()->subYears(18),
            'issuance_date'             => now()->subYear(),
            'expiration_date'           => now()->addYear(),
            'gender'                    => 'M',
            'profession'                => $this->faker->word(),
            'activity'                  => $this->faker->word(),
            'activity'                  => $this->faker->word(),
            'state'                     => 'NA',
            'issuance_country_id'       => $issuance_id = Country::factory()->create()->id,
            'nationality_country_id'    => $nationality_id = Country::factory()->create()->id,
        ]);

        $this->assertDatabaseHas('identities', [
            'identity_number'           => '123456789',
            'document_type'             => 'dni',
            'firstname'                 => 'Jhon',
            'lastname'                  => 'Doe',
            'issuance_country_id'       => $issuance_id,
            'nationality_country_id'    => $nationality_id,
        ]);
    }

    /** @test */
    public function an_identity_belongs_to_an_user()
    {
        $user = User::factory()->create();
        $identity = Identity::factory()->create([
            'user_id' => $user->id
        ]);

        $this->assertInstanceOf(User::class, $identity->user);
        $this->assertInstanceOf(Identity::class, $user->identity);
    }

    /** @test */
    public function identity_is_verified()
    {
        $identity = Identity::factory()->create();
        $this->assertFalse($identity->isVerified);

        $identity->verified_at = now();
        $identity->save();
        $this->assertTrue($identity->isVerified);
    }

    /** @test */
    public function identity_is_rejected()
    {
        $identity = Identity::factory()->create();
        $this->assertFalse($identity->isRejected);

        $identity->rejected_at = now();
        $identity->save();
        $this->assertTrue($identity->isRejected);
    }

    /** @test */
    public function a_user_has_one_issuance_country()
    {
        $country = Country::factory()->create();
        $identity = Identity::factory()->create([
            'issuance_country_id'   => $country->id
        ]);

        $this->assertInstanceOf(Country::class, $identity->issuanceCountry);
    }

    /** @test */
    public function a_user_has_one_nationality_country()
    {
        $country = Country::factory()->create();
        $identity = Identity::factory()->create([
            'nationality_country_id'   => $country->id
        ]);

        $this->assertInstanceOf(Country::class, $identity->nationalityCountry);
    }

}
