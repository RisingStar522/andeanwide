<?php

namespace Database\Seeders;

use App\Models\Bank;
use App\Models\Country;
use Illuminate\Support\Str;
use Illuminate\Database\Seeder;

class BankSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $JsonFileCountriesString = file_get_contents("banks.json", true);
        $banks = json_decode($JsonFileCountriesString);

        foreach($banks as $b){
            $country = Country::where('abbr', $b->country)->first();
            if($country) {
                $bank = new Bank();
                $bank->country_id = $country->id;
                $bank->name = $b->name;
                $bank->abbr = $b->abbr;
                $bank->code = Str::padLeft($b->code, 3, '0');
                $bank->is_active = true;
                $bank->save();
            }
        }
    }
}
