<?php

namespace App\Livewire\Auth;

use App\Enums\EotpPurpose;
use App\Livewire\Concerns\InteractsWithToasts;
use App\Models\User;
use App\Services\Commerce\CartService;
use App\Services\Commerce\WishlistService;
use App\Services\Security\AuditLogger;
use App\Services\Security\EotpService;
use App\Services\Security\TotpService;
use App\Support\Seo;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * The second step of a two-factor sign-in.
 *
 * Neither method is trusted on its own: the TOTP secret lives encrypted on the
 * user row, and the email code is compared against a hash with attempt limits.
 * A valid recovery code also works, and is consumed on use.
 */
#[Layout('layouts.guest')]
class TwoFactorChallenge extends Component
{
    use InteractsWithToasts;

    public string $code = '';

    public string $recoveryCode = '';

    public bool $usingRecovery = false;

    public function mount()
    {
        if (! session()->has('hanbell.2fa.user_id')) {
            return $this->redirect(route('login'));
        }

        $user = $this->pendingUser();

        // For an email-code user, issue the code as soon as the challenge opens.
        if ($user?->twoFactorMethod() === \App\Enums\TwoFactorMethod::Eotp) {
            $this->sendEmailCode();
        }

        return null;
    }

    private function pendingUser(): ?User
    {
        $id = session()->get('hanbell.2fa.user_id');

        return $id ? User::find($id) : null;
    }

    public function sendEmailCode(): void
    {
        $user = $this->pendingUser();

        if (! $user) {
            return;
        }

        try {
            $result = app(EotpService::class)->issue(
                $user->email,
                EotpPurpose::LoginChallenge,
                $user,
                request()->ip(),
            );
        } catch (\Throwable $e) {
            $this->toastError($e->getMessage());

            return;
        }

        \Illuminate\Support\Facades\Mail::raw(
            "Your HanbellShop sign-in code is {$result['code']}. It expires in ".
            EotpPurpose::LoginChallenge->ttlMinutes().' minutes.',
            fn ($mail) => $mail->to($user->email)->subject('Your HanbellShop sign-in code'),
        );

        $this->toastSuccess(__('hanbell.auth.two_factor.eotp_sent', ['email' => $user->email]));
    }

    public function verify(TotpService $totp, EotpService $eotp, AuditLogger $audit, CartService $carts, WishlistService $wishlist)
    {
        $user = $this->pendingUser();

        if (! $user) {
            return $this->redirect(route('login'));
        }

        $passed = false;

        if ($this->usingRecovery) {
            $remaining = $totp->consumeRecoveryCode(
                (array) ($user->two_factor_recovery_codes ?? []),
                $this->recoveryCode,
            );

            if ($remaining !== null) {
                $user->forceFill(['two_factor_recovery_codes' => $remaining])->save();
                $passed = true;
            }
        } elseif ($user->twoFactorMethod() === \App\Enums\TwoFactorMethod::Totp) {
            $passed = $totp->verify((string) $user->two_factor_secret, $this->code);
        } else {
            $passed = $eotp->verify($user->email, EotpPurpose::LoginChallenge, $this->code);
        }

        if (! $passed) {
            $this->toastError(__('hanbell.auth.two_factor.invalid'));

            return null;
        }

        $remember = (bool) session()->pull('hanbell.2fa.remember', false);
        session()->forget('hanbell.2fa.user_id');

        Auth::login($user, $remember);

        $user->forceFill(['last_login_at' => now(), 'last_login_ip' => request()->ip()])->save();

        $carts->mergeGuestCartInto($user, request()->cookie(CartService::COOKIE));
        $wishlist->mergeGuestWishlistInto($user, request()->cookie(WishlistService::COOKIE));

        $audit->loggedIn($user, twoFactor: true);

        return $this->redirect($user->postLoginRoute());
    }

    public function useRecovery(): void
    {
        $this->usingRecovery = true;
        $this->code = '';
    }

    public function useApp(): void
    {
        $this->usingRecovery = false;
        $this->recoveryCode = '';
    }

    public function render(): View
    {
        $user = $this->pendingUser();

        return view('livewire.auth.two-factor-challenge', [
            'user' => $user,
            'method' => $user?->twoFactorMethod() ?? \App\Enums\TwoFactorMethod::None,
            'seo' => app(Seo::class)->title(__('hanbell.auth.two_factor.title'))->noindex(),
        ]);
    }
}