<?php

namespace App\Livewire\Admin\Customers;

use App\Models\User;
use App\Support\Seo;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class CustomerShow extends Component
{
    public int $userId;

    public function mount(User $user): void
    {
        $this->userId = $user->id;
    }

    public function render(): View
    {
        $user = User::with(['addresses'])->findOrFail($this->userId);

        return view('livewire.admin.customers.customer-show', [
            'customer' => $user,
            'orders' => $user->orders()->withCount('items')->latest()->limit(20)->get(),
            'spentMinor' => (int) $user->orders()->whereNotNull('paid_at')->sum('total_minor'),
            'auditTrail' => \App\Models\AuditLog::where('user_id', $user->id)->latest('created_at')->limit(20)->get(),
            'loginAttempts' => \App\Models\LoginAttempt::where('email', $user->email)->latest('created_at')->limit(10)->get(),
            'seo' => app(Seo::class)->title($user->name)->noindex(),
        ]);
    }
}