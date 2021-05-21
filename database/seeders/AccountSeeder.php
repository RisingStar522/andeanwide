<?php

namespace Database\Seeders;

use App\Models\Account;
use Illuminate\Database\Seeder;

class AccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Account::factory()->create([
            'is_active' => true,
            'country_id' => 28,
            'currency_id' => 1,
            'bank_id' => 261,
            'label' => 'Banco de Chile',
            'bank_name' => 'Banco de Chile',
            'bank_account' => '123456789',
            'account_name' => 'AndeanWide SPA',
            'account_type' => 'Cuenta Corriente',
            'document_number' => '77.987.654-3',
            'description' => 'Servicio de SmartField de d-local',
        ]);
    }
}
