<?php

namespace App\Livewire\Auth;

use App\Enums\EotpPurpose;
use App\Livewire\Concerns\InteractsWithToasts;
use App\Services\Security\AuditLogger;
use App\Services\Security\EotpService;
use App\Support\Seo;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Email verification, with an emailed-code (EOTP) fallback for anyone whose
 * mail client mangles the signed link.
 */
#[Layout('layouts.guest')]
class VerifyEmail extends Component
{
    use InteractsWithToasts;

    public string $code = '';

    public function mount()
    {
        if (auth()->user()->hasVerifiedEmail()) {
            return $this->redirect(auth()->user()->postLoginRoute());
        }

        return null;
    }

    public function resend(AuditLogger $audit): void
    {
        try {
            $result = app(EotpService::class)->issue(
                auth()->user()->email,
                EotpPurpose::EmailVerification,
                auth()->user(),
                request()->ip(),
            );
        } catch (\Throwable $e) {
            $this->toastError($e->getMessage());

            return;
        }

        \Illuminate\Support\Facades\Mail::raw(
            "Your HanbellShop verification code is {$result['code']}. It expires in ".
            EotpPurpose::EmailVerification->ttlMinutes().' minutes.',
            fn ($mail) => $mail->to(auth()->user()->email)->subject('Verify your HanbellShop email'),
        );

        auth()->user()->sendEmailVerificationNotification();

        $audit->log('auth.email.verification_sent', auth()->user(), 'Verification email re-sent', [], auth()->user());

        $this->toastSuccess(__('hanbell.auth.verify_resent'));
    }

    public function verifyWithCode(EotpService $eotp, AuditLogger $audit)
    {
        $user = auth()->user();

        if (! $eotp->verify($user->email, EotpPurpose::EmailVerification, $this->code)) {
            $this->toastError(__('hanbell.auth.verification_invalid'));

            return null;
        }

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
        }

        $audit->emailVerified($user);
        $this->toastSuccess(__('hanbell.auth.verified'));

        return $this->redirect($user->postLoginRoute());
    }

    public function render(): View
    {
        return view('livewire.auth.verify-email', [
            'email' => auth()->user()->email,
            'seo' => app(Seo::class)->title(__('hanbell.auth.verify_title'))->noindex(),
        ]);
    }
}