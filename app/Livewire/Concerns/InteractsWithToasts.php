<?php

namespace App\Livewire\Concerns;

/**
 * The one way a Livewire action reports back to the shopper.
 *
 * Every mutating action dispatches a `toast` browser event, which
 * resources/js/app.js turns into a real toast. Components therefore never need
 * to render a flash-message block, and a mutating action never has to trigger a
 * full page reload just to say "done".
 */
trait InteractsWithToasts
{
    protected function toast(string $message, string $type = 'success', ?string $title = null): void
    {
        $this->dispatch('toast', message: $message, type: $type, title: $title);
    }

    protected function toastSuccess(string $message, ?string $title = null): void
    {
        $this->toast($message, 'success', $title);
    }

    protected function toastError(string $message, ?string $title = null): void
    {
        $this->toast($message, 'error', $title);
    }

    protected function toastInfo(string $message, ?string $title = null): void
    {
        $this->toast($message, 'info', $title);
    }

    protected function toastWarning(string $message, ?string $title = null): void
    {
        $this->toast($message, 'warning', $title);
    }
}
