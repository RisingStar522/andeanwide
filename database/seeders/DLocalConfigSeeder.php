<?php

namespace Database\Seeders;

use App\Models\DLocalConfig;
use Illuminate\Database\Seeder;

class DLocalConfigSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DLocalConfig::create([
            'name' => 'api.andeanwide.com',
            'url' => 'https://dpayout.andeanwide.com/',
            'grant_type' => 'client_credentials'
        ]);
    }
}
