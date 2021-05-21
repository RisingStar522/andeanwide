<?php

namespace Database\Seeders;

use App\Models\Country;
use Illuminate\Database\Seeder;

class CountrySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $JsonFileCountriesString = file_get_contents("countries_db.json", true);
        $countries = json_decode($JsonFileCountriesString);

        foreach($countries as $c){
            $country = new Country;
            $country->name = $c->country;
            $country->abbr = $c->abbreviation;
            $country->currency = $c->name;
            $country->currency_code = $c->code;
            $country->is_active = false;
            $country->save();
        }
    }
}
