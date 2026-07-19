<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class AdminUserFixtureSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            throw new RuntimeException(
                'AdminUserFixtureSeeder must not run in production.'
            );
        }

        $user = User::query()->firstOrNew([
            'email' => 'eduardongua@hotmail.com',
        ]);

        $user->name = 'Eduardo';
        $user->password = Hash::make('password');
        $user->is_admin = true;
        $user->email_verified_at ??= now();

        $user->save();
    }
}