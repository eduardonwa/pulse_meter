<?php

namespace Database\Seeders;

use App\Models\PulsePreset;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PulsePresetSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach(config('presets') as $preset) {
            PulsePreset::updateOrCreate(
                [
                    'key' => $preset['key']
                ],
                [
                    'numerator' => $preset['numerator'],
                    'denominator' => $preset['denominator'],
                    'grouping' => $preset['grouping'],
                    'pattern' => $preset['pattern'],
                ]
            );
        }
    }
}
