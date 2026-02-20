<?php

namespace Database\Seeders;

use App\Models\BaseUnit;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;

class UnitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $units = File::json(database_path('data/units.json'));

        foreach ($units['base_units'] as $unit) {
            $baseUnit = BaseUnit::updateOrCreate([
                'name' => $unit['name'],
            ], [
                'symbol' => $unit['symbol'],
            ]);

            $unitOptions = Arr::wrap($unit['unit_options'] ?? []);

            if ($unitOptions) {
                foreach ($unitOptions as $option) {
                    $baseUnit->unitOptions()->firstOrCreate([
                        'name' => $option['name'],
                    ], [
                        'multiplier' => $option['multiplier'],
                        'is_default' => true,
                    ]);
                }

                continue;
            }
            // Else
            $baseUnit->unitOptions()->firstOrCreate([
                'name' => "1 $baseUnit->symbol",
            ], [
                'multiplier' => 1,
                'is_default' => true,
            ]);
        }
    }
}
