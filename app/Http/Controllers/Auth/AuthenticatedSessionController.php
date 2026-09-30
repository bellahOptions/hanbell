<?php

namespace App\Http\Controllers\Auth;

use App\Services\Commerce\CartService;
use App\Services\Security\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthenticatedSessionController
{
    /**
     * Sign out. The cart cookie is deliberately left alone: a shopper who signs
     * out mid-browse keeps the bag they built.
     */
    public function destroy(Request $request, AuditLogger $audit)
    {
        $user = $request->user();

        if ($user) {
            $audit->log('auth.logout', $user, 'Signed out', [], $user);
        }

        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('storefront.home')
            ->with('toast', [
                'message' => __('hanbell.auth.signed_out'),
                'type' => 'info',
            ]);
    }
}