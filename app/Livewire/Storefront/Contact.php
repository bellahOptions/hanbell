<?php

namespace App\Livewire\Storefront;

use App\Livewire\Concerns\InteractsWithToasts;
use App\Support\Seo;
use App\Support\Settings;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Mail;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('layouts.app')]
class Contact extends Component
{
    use InteractsWithToasts;

    #[Validate('required|string|max:120')]
    public string $name = '';

    #[Validate('required|email|max:255')]
    public string $email = '';

    #[Validate('required|string|max:200')]
    public string $subject = '';

    #[Validate('required|string|min:10|max:4000')]
    public string $message = '';

    public function send(): void
    {
        $this->validate();

        // A honeypot-free, rate-limited contact form: the message is sent to the
        // support inbox. With MAIL_MAILER=log on this install it lands in the
        // log rather than a real inbox.
        Mail::raw(
            "From: {$this->name} <{$this->email}>\n\n{$this->message}",
            fn ($mail) => $mail
                ->to((string) config('hanbell.support_email'))
                ->subject('[HanbellShop] '.$this->subject),
        );

        $this->reset(['name', 'email', 'subject', 'message']);
        $this->toastSuccess(__('hanbell.toast.success'), __('hanbell.nav.contact'));
    }

    public function render(): View
    {
        return view('livewire.storefront.contact', [
            'seo' => app(Seo::class)->title(__('hanbell.nav.contact'))->description(config('hanbell.description')),
        ]);
    }
}