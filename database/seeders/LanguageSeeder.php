<?php

namespace Database\Seeders;

use App\Models\Language;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class LanguageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $languages = File::json(database_path('data/languages.json'));

        foreach ($languages as $data) {
            Language::updateOrCreate([
                'prefix' => $data['prefix'],
            ], [
                'name' => $data['name'],
            ]);
        }
    }
}
