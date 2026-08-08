<?php

namespace App\Listeners;

use App\Mail\WelcomeNewUser;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Mail;

class SendWelcomeEmail
{  
    /**
     * Handle the event.
     *
     * @param Verified $event
     * @return void
     */
    public function handle(Verified $event): void
    {
        /** @var User $user */
        $user = $event->user;

        Mail::to($user->email)->send(new WelcomeNewUser($user));
    }
}
