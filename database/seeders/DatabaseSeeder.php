<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // \App\Models\User::factory(10)->create();
        $this->call([
            RoleSeeder::class,
            CountrySeeder::class,
            BankSeeder::class,
            CurrencySeeder::class,
            PairSeeder::class,
            ParamSeeder::class,
            PrioritySeeder::class,
            PaymentTypeSeeder::class,
            DLocalConfigSeeder::class,
            AccountSeeder::class,
            UserSeeder::class,
        ]);
    }
}
