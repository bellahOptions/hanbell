<?php

namespace App\Livewire\Storefront;

use App\Livewire\Concerns\InteractsWithToasts;
use App\Models\NewsletterSubscriber;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Validate;
use Livewire\Component;

class NewsletterSignup extends Component
{
    use InteractsWithToasts;

    #[Validate('required|email|max:255')]
    public string $email = '';

    public function subscribe(): void
    {
        $this->validate();

        $existing = NewsletterSubscriber::where('email', strtolower(trim($this->email)))->first();

        if ($existing?->is_active) {
            $this->toastInfo(__('hanbell.newsletter.already_subscribed'));
            $this->email = '';

            return;
        }

        NewsletterSubscriber::subscribe($this->email, 'footer');

        $this->email = '';
        $this->toastSuccess(__('hanbell.newsletter.subscribed'));
    }

    public function render(): View
    {
        return view('livewire.storefront.newsletter-signup');
    }
}
