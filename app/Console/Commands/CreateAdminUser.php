<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CreateAdminUser extends Command
{
    protected $signature = 'user:create-admin
        {email : User email}
        {--name= : User name}
        {--password= : User password}';

    protected $description = 'Create or update an admin user';

    public function handle(): int
    {
        $email = strtolower(trim($this->argument('email')));

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Invalid email.');

            return self::FAILURE;
        }

        $name = $this->option('name') ?: Str::before($email, '@');

        $user = User::where('email', $email)->first();

        if (! $user) {
            $password = $this->option('password') ?: $this->secret('Password');

            if (! $password || strlen($password) < 8) {
                $this->error('Password is required and must be at least 8 characters.');

                return self::FAILURE;
            }

            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($password),
                'is_admin' => true,
                'email_verified_at' => now(),
            ]);

            $this->info("Admin user created: {$user->email}");

            return self::SUCCESS;
        }

        $user->forceFill([
            'is_admin' => true,
            'email_verified_at' => $user->email_verified_at ?? now(),
        ])->save();

        $this->info("Admin access granted to existing user: {$user->email}");

        return self::SUCCESS;
    }
}