<?php

namespace App\Livewire\Auth;

use App\Livewire\Concerns\InteractsWithToasts;
use App\Models\LoginAttempt;
use App\Models\User;
use App\Services\Commerce\CartService;
use App\Services\Commerce\WishlistService;
use App\Services\Security\AuditLogger;
use App\Support\Seo;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

/**
 * Sign-in.
 *
 * Throttled by email + IP, and every attempt — successful or not — is recorded.
 * A user whose account has two-factor enabled is sent to the challenge instead
 * of being logged in directly; the challenge completes the login.
 */
#[Layout('layouts.guest')]
class Login extends Component
{
    use InteractsWithToasts;

    #[Validate('required|email|max:255')]
    public string $email = '';

    #[Validate('required|string')]
    public string $password = '';

    public bool $remember = false;

    public function login(AuditLogger $audit, CartService $carts, WishlistService $wishlist)
    {
        $this->validate();

        $throttleKey = Str::transliterate(Str::lower($this->email).'|'.request()->ip());

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            LoginAttempt::record($this->email, false, 'throttled', request()->ip(), request()->userAgent());

            $this->addError('email', __('hanbell.auth.too_many_attempts', ['seconds' => $seconds]));

            return null;
        }

        if (! Auth::validate(['email' => $this->email, 'password' => $this->password])) {
            RateLimiter::hit($throttleKey, 60);

            LoginAttempt::record($this->email, false, 'invalid_credentials', request()->ip(), request()->userAgent());

            // Deliberately identical to the "no such account" case: nothing here
            // should reveal whether an email is registered.
            $this->addError('password', __('hanbell.auth.invalid_credentials'));

            return null;
        }

        /** @var User $user */
        $user = User::where('email', $this->email)->firstOrFail();

        RateLimiter::clear($throttleKey);

        // Two-factor: park the user id in the session and hand off to the
        // challenge rather than authenticating now.
        if ($user->hasTwoFactorEnabled()) {
            session()->put('hanbell.2fa.user_id', $user->id);
            session()->put('hanbell.2fa.remember', $this->remember);

            LoginAttempt::record($this->email, true, '2fa_required', request()->ip(), request()->userAgent());

            return $this->redirect(route('two-factor.challenge'));
        }

        Auth::login($user, $this->remember);

        $this->afterLogin($user, $audit, $carts, $wishlist);

        return $this->redirect($this->intended($user));
    }

    private function afterLogin(User $user, AuditLogger $audit, CartService $carts, WishlistService $wishlist): void
    {
        $user->forceFill([
            'last_login_at' => now(),
            'last_login_ip' => request()->ip(),
        ])->save();

        // Fold anything gathered as a guest into the account.
        $carts->mergeGuestCartInto($user, request()->cookie(CartService::COOKIE));
        $wishlist->mergeGuestWishlistInto($user, request()->cookie(WishlistService::COOKIE));

        LoginAttempt::record($this->email, true, null, request()->ip(), request()->userAgent());
        $audit->loggedIn($user);
    }

    private function intended(User $user): string
    {
        // redirect()->intended() lets a deep link win, but the default is always
        // role-appropriate: an admin lands in the admin panel.
        return redirect()->intended($user->postLoginRoute())->getTargetUrl();
    }

    public function render(): View
    {
        return view('livewire.auth.login', [
            'seo' => app(Seo::class)->title(__('hanbell.auth.login_title'))->noindex(),
        ]);
    }
}