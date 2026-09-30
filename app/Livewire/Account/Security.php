<?php

namespace App\Livewire\Account;

use App\Enums\TwoFactorMethod;
use App\Livewire\Concerns\InteractsWithToasts;
use App\Models\AuditLog;
use App\Models\LoginAttempt;
use App\Services\Security\AuditLogger;
use App\Services\Security\TotpService;
use App\Support\Seo;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Two-factor enrolment and account security.
 *
 * The secret is saved but `two_factor_confirmed_at` stays null until a code has
 * actually verified. Without that gate a mistyped secret would arm 2FA
 * immediately and lock the user out of their own account on the next sign-in.
 */
#[Layout('layouts.app')]
class Security extends Component
{
    use InteractsWithToasts;

    public string $method = 'none';

    public string $setupSecret = '';

    public string $setupQr = '';

    public string $confirmCode = '';

    public bool $showRecoveryCodes = false;

    /** @var array<int,string> */
    public array $recoveryCodes = [];

    public function mount(): void
    {
        $this->method = auth()->user()->twoFactorMethod()->value;
    }

    public function beginTotpSetup(TotpService $totp): void
    {
        $user = auth()->user();
        $secret = $totp->generateSecret();

        $user->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_method' => TwoFactorMethod::Totp,
            'two_factor_confirmed_at' => null,
        ])->save();

        $this->setupSecret = $secret;
        $this->setupQr = $totp->qrCodeSvg($totp->provisioningUri($user, $secret));
        $this->confirmCode = '';
        $this->method = TwoFactorMethod::Totp->value;
    }

    public function confirmTotp(TotpService $totp, AuditLogger $audit): void
    {
        $user = auth()->user();

        if (! $totp->verify((string) $user->two_factor_secret, $this->confirmCode)) {
            $this->toastError(__('hanbell.account.invalid_code'));

            return;
        }

        $codes = $totp->generateRecoveryCodes();

        $user->forceFill([
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => $codes,
        ])->save();

        $this->recoveryCodes = $codes;
        $this->showRecoveryCodes = true;
        $this->setupQr = '';
        $this->setupSecret = '';
        $this->confirmCode = '';

        $audit->twoFactorEnabled($user, 'totp');
        $this->toastSuccess(__('hanbell.account.enabled_success'));
    }

    public function enableEotp(AuditLogger $audit): void
    {
        $user = auth()->user();

        $user->forceFill([
            'two_factor_method' => TwoFactorMethod::Eotp,
            'two_factor_confirmed_at' => now(),
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
        ])->save();

        $this->method = TwoFactorMethod::Eotp->value;
        $this->setupQr = '';
        $this->setupSecret = '';

        $audit->twoFactorEnabled($user, 'eotp');
        $this->toastSuccess(__('hanbell.account.enabled_success'));
    }

    public function disable(AuditLogger $audit): void
    {
        $user = auth()->user();

        $user->forceFill([
            'two_factor_method' => TwoFactorMethod::None,
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        $this->method = TwoFactorMethod::None->value;
        $this->showRecoveryCodes = false;
        $this->setupQr = '';
        $this->setupSecret = '';

        $audit->twoFactorDisabled($user);
        $this->toastInfo(__('hanbell.account.disabled_success'));
    }

    public function regenerateRecoveryCodes(TotpService $totp, AuditLogger $audit): void
    {
        $codes = $totp->generateRecoveryCodes();

        auth()->user()->forceFill(['two_factor_recovery_codes' => $codes])->save();

        $this->recoveryCodes = $codes;
        $this->showRecoveryCodes = true;

        $audit->log('security.2fa.recovery_regenerated', auth()->user(), 'Recovery codes regenerated', [], auth()->user());
        $this->toastSuccess(__('hanbell.account.regenerate_codes'));
    }

    public function render(): View
    {
        $user = auth()->user();

        return view('livewire.account.security', [
            'user' => $user,
            'enabled' => $user->hasTwoFactorEnabled(),
            'loginActivity' => LoginAttempt::where('email', $user->email)->latest('created_at')->limit(10)->get(),
            'auditTrail' => AuditLog::where('user_id', $user->id)->latest('created_at')->limit(15)->get(),
            'seo' => app(Seo::class)->title(__('hanbell.account.security_title'))->noindex(),
        ]);
    }
}