<?php

namespace Database\Seeders;

use App\Models\BusinessType;
use App\Models\ProductCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class BusinessTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $businessTypes = File::json(database_path('data/business-types.json'));

        foreach ($businessTypes as $data) {
            $businessType = BusinessType::updateOrCreate([
                'name' => $data['name'],
            ], []);

            ProductCategory::firstOrCreate(['name' => 'Machinery', 'business_type_id' => $businessType->id, 'is_machinery' => true]);

        }
    }
}
