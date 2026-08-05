<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\Plans\UpgradeToPro;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('user:make-pro {email}')]
#[Description('Give Pro access to a user')]
class MakeUserPro extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(UpgradeToPro $upgradeToPro): int
    {
        $email = (string) $this->argument('email');

        $user = User::query()
            ->where('email', '=', $email)
            ->first();

        if ($user === null) {
            $this->error('User not found.');

            return self::FAILURE;
        }

        $upgradeToPro->upgrade($user);
        $this->info("{$user->email} is now Pro.");

        return self::SUCCESS;
    }
}