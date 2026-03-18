<?php

namespace Database\Seeders;

use App\Models\Partners;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PartnersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $partners = [
            [
                'name' => 'Partner 1',
                'logo' => 'partner1_logo.png',
            ],
            [
                'name' => 'Partner 2',
                'logo' => 'partner2_logo.png',
            ],
        ];
        collect($partners)->each(function ($partner) {
            Partners::create($partner);
        });
    }
}
