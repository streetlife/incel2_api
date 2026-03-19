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
                'name' => 'Amadeous',
                'logo' => 'partner1_logo.png',
                "type" => "Airline",
            ],
            [
                'name' => 'Rezlive',
                'logo' => 'partner2_logo.png',
                "type" => "Hotel",
            ],
             [
                'name' => 'RAYNA_KEY',
                'logo' => 'partner1_logo.png',
                "type" => "Tour",
            ],
            [
                'name' => 'Rezlive',
                'logo' => 'partner2_logo.png',
                "type" => "Hotel",
            ],
        ];
        collect($partners)->each(function ($partner) {
            Partners::create($partner);
        });
    }
}
