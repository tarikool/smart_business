<?php

namespace Database\Seeders;

use App\Models\Currency;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class CurrencySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $currencies = File::json(database_path('data/currencies.json'));

        foreach ($currencies as $data) {
            Currency::updateOrCreate([
                'code' => $data['code'],
            ], [
                'name' => $data['name'],
                'symbol' => $data['symbol'],
            ]);
        }
    }
}
