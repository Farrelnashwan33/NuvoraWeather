<?php

namespace Database\Seeders;

use App\Models\FeaturedCity;
use Illuminate\Database\Seeder;

class FeaturedCitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $cities = [
            [
                'city_name' => 'Bandung',
                'country' => 'Indonesia',
                'latitude' => -6.9175,
                'longitude' => 107.6191,
                'is_active' => true,
            ],
            [
                'city_name' => 'Jakarta',
                'country' => 'Indonesia',
                'latitude' => -6.2088,
                'longitude' => 106.8456,
                'is_active' => true,
            ],
            [
                'city_name' => 'Tokyo',
                'country' => 'Japan',
                'latitude' => 35.6762,
                'longitude' => 139.6503,
                'is_active' => true,
            ],
            [
                'city_name' => 'Singapore',
                'country' => 'Singapore',
                'latitude' => 1.3521,
                'longitude' => 103.8198,
                'is_active' => true,
            ],
            [
                'city_name' => 'London',
                'country' => 'United Kingdom',
                'latitude' => 51.5074,
                'longitude' => -0.1278,
                'is_active' => true,
            ],
            [
                'city_name' => 'New York',
                'country' => 'United States',
                'latitude' => 40.7128,
                'longitude' => -74.0060,
                'is_active' => true,
            ],
            [
                'city_name' => 'Sydney',
                'country' => 'Australia',
                'latitude' => -33.8688,
                'longitude' => 151.2093,
                'is_active' => true,
            ],
            [
                'city_name' => 'Paris',
                'country' => 'France',
                'latitude' => 48.8566,
                'longitude' => 2.3522,
                'is_active' => true,
            ],
            [
                'city_name' => 'Dubai',
                'country' => 'United Arab Emirates',
                'latitude' => 25.2048,
                'longitude' => 55.2708,
                'is_active' => true,
            ],
            [
                'city_name' => 'Seoul',
                'country' => 'South Korea',
                'latitude' => 37.5665,
                'longitude' => 126.9780,
                'is_active' => true,
            ],
        ];

        foreach ($cities as $city) {
            FeaturedCity::updateOrCreate(
                ['city_name' => $city['city_name'], 'country' => $city['country']],
                $city
            );
        }
    }
}
