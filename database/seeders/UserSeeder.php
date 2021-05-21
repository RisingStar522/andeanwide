<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $user = User::factory()->create([
            'name' => 'mik3dev',
            'email' => 'miguel.acosta1978@gmail.com',
            'email_verified_at' => now(),
            'password' => Hash::make('Abc1234$')
        ]);
        $user->assignRole('user');

        $user = User::factory()->create([
            'name' => 'soporte',
            'email' => 'soporte@andeanwide.com',
            'email_verified_at' => now(),
            'password' => Hash::make('Abc1234$')
        ]);
        $user->assignRole(['base', 'admin', 'super_admin', 'compliance']);

        $user = User::factory()->create([
            'name' => 'contacto',
            'email' => 'contacto@andeanwide.com',
            'email_verified_at' => now(),
            'password' => Hash::make('Abc1234$')
        ]);
        $user->assignRole(['base', 'admin', 'super_admin', 'compliance']);

        $user = User::factory()->create([
            'name' => 'compliance',
            'email' => 'compliance@andeanwide.com',
            'email_verified_at' => now(),
            'password' => Hash::make('Abc1234$')
        ]);
        $user->assignRole(['base', 'compliance']);

        $user = User::factory()->create([
            'name' => 'admin',
            'email' => 'admin@andeanwide.com',
            'email_verified_at' => now(),
            'password' => Hash::make('Abc1234$')
        ]);
        $user->assignRole(['base', 'admin']);
    }
}
