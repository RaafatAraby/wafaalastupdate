<?php

namespace Database\Seeders;

use App\Models\Country;
use Illuminate\Database\Seeder;

class CountrySeeder extends Seeder
{
    public function run(): void
    {
        $countries = [
            ['name_ar' => 'فلسطين', 'name_en' => 'Palestine', 'iso2' => 'PS', 'sort_order' => 1],
            ['name_ar' => 'مصر', 'name_en' => 'Egypt', 'iso2' => 'EG', 'sort_order' => 2],
            ['name_ar' => 'تركيا', 'name_en' => 'Turkey', 'iso2' => 'TR', 'sort_order' => 3],
            ['name_ar' => 'سوريا', 'name_en' => 'Syria', 'iso2' => 'SY', 'sort_order' => 4],
            ['name_ar' => 'لبنان', 'name_en' => 'Lebanon', 'iso2' => 'LB', 'sort_order' => 5],
            ['name_ar' => 'الأردن', 'name_en' => 'Jordan', 'iso2' => 'JO', 'sort_order' => 6],
        ];

        foreach ($countries as $country) {
            Country::updateOrCreate(
                ['iso2' => $country['iso2']],
                $country + ['is_active' => true]
            );
        }
    }
}
