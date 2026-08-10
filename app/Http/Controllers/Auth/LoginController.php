<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Socialite;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;

class LoginController
{
    public function redirectToProvider()
    {
        return Socialite::driver('google')->redirect();
    }

    public function handleProviderCallback()
    {
        $googleUser = Socialite::driver('google')->user();

        $user = User::query()
            ->where('google_id', $googleUser->id)
            ->orWhere('email', $googleUser->email)
            ->first();
        
        if ($user) {
            $wasUnverified = is_null($user->email_verified_at);

            $user->google_id = $googleUser->id;
            $user->email_verified_at ??= now();
            $user->save();
         
            if ($wasUnverified) {
                event(new Verified($user));
            }

        } else {
            $user = User::create([
                'name' => $googleUser->name,
                'email' => $googleUser->email,
                'google_id' => $googleUser->id,
                'email_verified_at' => now(),
                'password' => bcrypt(str()->random(40)),
            ]);

            event(new Registered($user));
            event(new Verified($user));
        }

        Auth::login($user);

        request()->session()->regenerate();
        
        return redirect()->intended('/');
    }
}