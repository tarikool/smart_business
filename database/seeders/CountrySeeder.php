<?php

namespace Database\Seeders;

use App\Models\Country;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class CountrySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $countries = File::json(database_path('data/countries.json'));

        foreach ($countries as $data) {
            Country::updateOrCreate([
                'iso_code' => $data['iso_code'],
            ], [
                'iso_code_3' => $data['iso_code_3'],
                'name' => $data['name'],
                'language_id' => $data['language_id'] ?: 7,
                'available_languages' => $data['available_languages'] ?: [7],
                'currency_id' => $data['currency_id'] ?: 10,
            ]);
        }
    }
}
