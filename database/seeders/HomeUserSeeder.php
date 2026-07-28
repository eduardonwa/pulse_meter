<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class HomeUserSeeder extends Seeder
{
    public function run(): void
    {
        // Evita crear esta cuenta accidentalmente en producción.
        if (app()->isProduction()) {
            return;
        }

        $user = User::firstOrNew([
            'email' => 'user@dorelog.test',
        ]);

        $user->forceFill([
            'name' => 'DoreLog Test User',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'is_admin' => false,
        ])->save();
    }
}