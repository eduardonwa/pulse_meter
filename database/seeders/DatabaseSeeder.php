<?php

namespace Database\Seeders;

use Database\Seeders\AdminUserFixtureSeeder;
use Database\Seeders\HomeUserSeeder;
use Database\Seeders\PostSeeder;
use Database\Seeders\PulsePresetSeeder;
use Database\Seeders\TrafficLogFixtureSeeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            PulsePresetSeeder::class,
            HomeUserSeeder::class,
            AdminUserFixtureSeeder::class,
            ProductEventFixtureSeeder::class,
            TrafficLogFixtureSeeder::class,
            PostSeeder::class
        ]);
    }
}
