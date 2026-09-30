<?php

namespace App\Livewire\Auth;

use App\Livewire\Concerns\InteractsWithToasts;
use App\Services\Security\AuditLogger;
use App\Support\Seo;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.guest')]
class ResetPassword extends Component
{
    use InteractsWithToasts;

    public string $token = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function mount(string $token): void
    {
        $this->token = $token;
        $this->email = (string) request()->query('email', '');
    }

    /**
     * Named resetPassword(), not reset(): Livewire's base Component already
     * defines reset(...$properties) for resetting public properties, and
     * overriding it with an incompatible signature is a fatal error.
     */
    public function resetPassword(AuditLogger $audit)
    {
        $this->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'confirmed', PasswordRule::defaults()],
        ]);

        $status = Password::reset(
            [
                'email' => $this->email,
                'password' => $this->password,
                'password_confirmation' => $this->password_confirmation,
                'token' => $this->token,
            ],
            function ($user, string $password) use ($audit): void {
                $user->forceFill(['password' => Hash::make($password)])->save();

                // Changing a password rotates the remember token, which signs
                // out every other session.
                $user->setRememberToken(\Illuminate\Support\Str::random(60));
                $user->save();

                $audit->log('auth.password.reset', $user, 'Password reset', [], $user);
            },
        );

        if ($status !== Password::PasswordReset) {
            $this->toastError(__($status));

            return null;
        }

        $this->toastSuccess(__('hanbell.auth.password_reset_done'));

        return $this->redirect(route('login'));
    }

    public function render(): View
    {
        return view('livewire.auth.reset-password', [
            'seo' => app(Seo::class)->title(__('hanbell.auth.reset_title'))->noindex(),
        ]);
    }
}