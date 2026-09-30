<?php

namespace App\Livewire\Account;

use App\Support\Seo;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Dashboard extends Component
{
    public function render(): View
    {
        $user = auth()->user();

        return view('livewire.account.dashboard', [
            'user' => $user,
            'recentOrders' => $user->orders()->with('items')->latest()->limit(4)->get(),
            'orderCount' => $user->orders()->count(),
            'addressCount' => $user->addresses()->count(),
            'wishlistCount' => app(\App\Services\Commerce\WishlistService::class)->count(),
            'spentMinor' => (int) $user->orders()->whereNotNull('paid_at')->sum('total_minor'),
            'seo' => app(Seo::class)->title(__('hanbell.account.title'))->noindex(),
        ]);
    }
}