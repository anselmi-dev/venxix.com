<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Revoltify\Subscriptionify\Models\Plan;
use Throwable;

class GoogleAuthController extends Controller
{
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback(): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (InvalidStateException|Throwable) {
            return redirect()
                ->route('login')
                ->with('error', __('ui.auth.google_error'));
        }

        $user = $this->findOrCreateUser($googleUser);

        Auth::login($user, remember: true);

        return redirect()->route('dashboard');
    }

    private function findOrCreateUser(SocialiteUser $googleUser): User
    {
        $user = User::query()->where('google_id', $googleUser->getId())->first();

        if ($user !== null) {
            return $user;
        }

        $user = User::query()->where('email', $googleUser->getEmail())->first();

        if ($user !== null) {
            $user->forceFill([
                'google_id' => $googleUser->getId(),
                'email_verified_at' => $user->email_verified_at ?? now(),
            ])->save();

            return $user;
        }

        $user = User::create([
            'name' => $googleUser->getName() ?? $googleUser->getNickname() ?? $googleUser->getEmail(),
            'email' => $googleUser->getEmail(),
            'google_id' => $googleUser->getId(),
        ]);

        $user->forceFill(['email_verified_at' => now()])->save();

        $free = Plan::query()->where('slug', 'free')->first();

        if ($free !== null) {
            $user->subscribe($free);
        }

        return $user;
    }
}
