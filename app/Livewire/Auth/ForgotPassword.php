<?php

namespace App\Livewire\Auth;

use App\Livewire\Concerns\InteractsWithToasts;
use App\Support\Seo;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Password;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('layouts.guest')]
class ForgotPassword extends Component
{
    use InteractsWithToasts;

    #[Validate('required|email|max:255')]
    public string $email = '';

    public function send(): void
    {
        $this->validate();

        Password::sendResetLink(['email' => $this->email]);

        // The same message regardless of whether the address exists: the form
        // must not be usable to enumerate registered emails.
        $this->toastSuccess(__('hanbell.auth.password_reset_sent'));
        $this->email = '';
    }

    public function render(): View
    {
        return view('livewire.auth.forgot-password', [
            'seo' => app(Seo::class)->title(__('hanbell.auth.forgot_title'))->noindex(),
        ]);
    }
}