<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Administrators do not shop.
 *
 * HanbellShop deliberately separates the two roles: an admin account has no
 * cart, no wishlist and no checkout. Bouncing them out of the storefront
 * account pages removes a whole class of confusion (an admin wondering why
 * their test order appeared in customer reports) and keeps the customer data
 * model clean.
 *
 * This is a product decision, not a technical limit. If a future requirement
 * needs an admin who also shops, this middleware plus the two guard clauses in
 * ProductShow::addToCart() and the wishlist toggle are the places to revisit.
 */
class RedirectAdminsFromStorefront
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->isAdmin()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Administrator accounts do not have a shopping cart or wishlist.',
                ], 403);
            }

            return redirect()
                ->route('admin.dashboard')
                ->with('status', 'Administrator accounts do not have a shopping area — you have been returned to the admin panel.');
        }

        return $next($request);
    }
}
