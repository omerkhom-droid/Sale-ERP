<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\Brand;
use App\Models\Unit;

class MasterDataSeeder extends Seeder
{
    public function run(): void
    {
        // Categories
        $categories = [
            'فلاتر',
            'زيوت',
            'بواجي',
            'سيور',
            'فرامل',
            'بطاريات',
            'مساعدات',
            'قطع كهربائية',
            'قطع محرك',
            'إكسسوارات',
        ];

        foreach ($categories as $name) {
            Category::firstOrCreate([
                'category_name' => $name
            ],[
                'is_active' => true
            ]);
        }

        // Brands
        $brands = [
            'Toyota',
            'Nissan',
            'Hyundai',
            'Kia',
            'Honda',
            'Ford',
            'Bosch',
            'Denso',
            'NGK',
            'Mobil',
        ];

        foreach ($brands as $name) {
            Brand::firstOrCreate([
                'brand_name' => $name
            ],[
                'is_active' => true
            ]);
        }

        // Units
        $units = [
            ['قطعة','PCS'],
            ['علبة','BOX'],
            ['كرتون','CTN'],
            ['طقم','SET'],
            ['حبة','EA'],
        ];

        foreach ($units as $unit) {

            Unit::firstOrCreate([
                'unit_name' => $unit[0]
            ],[
                'unit_code' => $unit[1],
                'is_active' => true
            ]);

        }
    }
}