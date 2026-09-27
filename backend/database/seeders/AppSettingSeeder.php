<?php

namespace Database\Seeders;

use App\Models\AppSetting;
use Illuminate\Database\Seeder;

class AppSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            'app_name' => 'Nuvora Weather',
            'tagline' => 'Know Your Weather. Plan Your Day.',
            'cache_ttl_minutes' => '15',
            'default_city' => 'Bandung',
            'default_country' => 'Indonesia',
            'default_lat' => '-6.9175',
            'default_lon' => '107.6191',
            'system_notice' => 'Atmospheric satellites active. High precision radar online.',
            'rate_limit_per_min' => '60',
        ];

        foreach ($settings as $key => $val) {
            AppSetting::updateOrCreate(['key' => $key], ['value' => $val]);
        }
    }
}
