<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\FuelType;

class FuelTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = ['Essence', 'Gazoil', 'Gaz'];

        foreach ($types as $type) {
            FuelType::firstOrCreate(['name' => $type]);
        }
    }
}
