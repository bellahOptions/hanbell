<?php

namespace App\Livewire\Auth;

use App\Livewire\Concerns\InteractsWithToasts;
use App\Models\User;
use App\Services\Commerce\CartService;
use App\Services\Commerce\WishlistService;
use App\Services\Security\AuditLogger;
use App\Services\Security\EotpService;
use App\Support\Seo;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('layouts.guest')]
class Register extends Component
{
    use InteractsWithToasts;

    #[Validate('required|string|max:120')]
    public string $name = '';

    #[Validate('required|email|max:255|unique:users,email')]
    public string $email = '';

    #[Validate('nullable|string|max:32')]
    public string $phone = '';

    #[Validate('required|string|confirmed')]
    public string $password = '';

    public string $password_confirmation = '';

    public bool $terms = false;

    public function register(AuditLogger $audit, CartService $carts, WishlistService $wishlist)
    {
        $this->validate([
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
            'terms' => ['accepted'],
        ], [
            'terms.accepted' => __('hanbell.auth.agree_terms', ['terms' => '', 'privacy' => '']),
        ]);

        $user = User::create([
            'name' => $this->name,
            'email' => strtolower($this->email),
            'phone' => $this->phone,
            'password' => Hash::make($this->password),
            'locale' => app()->getLocale(),
        ]);

        $user->assignRole('customer');

        Auth::login($user);

        $carts->mergeGuestCartInto($user, request()->cookie(CartService::COOKIE));
        $wishlist->mergeGuestWishlistInto($user, request()->cookie(WishlistService::COOKIE));

        $audit->registered($user);

        // Send the verification link; checkout and order tracking stay behind it.
        $user->sendEmailVerificationNotification();

        $this->toastSuccess(__('hanbell.auth.account_created', ['name' => $user->firstName()]));

        return $this->redirect(route('verification.notice'));
    }

    public function render(): View
    {
        return view('livewire.auth.register', [
            'seo' => app(Seo::class)->title(__('hanbell.auth.register_title'))->noindex(),
        ]);
    }
}