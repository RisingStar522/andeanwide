<?php

namespace Database\Factories;

use App\Models\Bank;
use App\Models\User;
use App\Models\Country;
use App\Models\Recipient;
use Spatie\Permission\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

class RecipientFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Recipient::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $user = User::factory()->create();
        $role = Role::where('name', 'user')->first();
        if($role) {
            $user->assignRole('user');
        }

        return [
            'user_id'       => $user,
            'country_id'    => Country::factory()->create(),
            'bank_id'       => Bank::factory()->create(),
            'name'          => $this->faker->firstName(),
            'lastname'      => $this->faker->lastName(),
            'dni'           => $this->faker->ean8(),
            'document_type' => 'PASS',
            'phone'         => $this->faker->phoneNumber(),
            'email'         => $this->faker->email(),
            'bank_name'     => $this->faker->word(),
            'bank_account'  => $this->faker->bankAccountNumber(),
            'account_type'  => 'C',
            'bank_code'     => $this->faker->ean8(),
            'address'       => $this->faker->address(),
        ];
    }
}
