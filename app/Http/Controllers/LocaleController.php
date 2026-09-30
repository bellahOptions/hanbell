<?php

namespace App\Http\Controllers;

use App\Services\Security\AuditLogger;
use App\Support\Locale;
use Illuminate\Http\Request;

class LocaleController
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * Switch language. Persists the choice in the session and a long-lived
     * cookie, and — for a signed-in user — on their profile, so the preference
     * survives a new device.
     */
    public function update(Request $request, string $locale)
    {
        $resolved = Locale::normalise($locale);

        if ($resolved === null) {
            abort(404);
        }

        Locale::remember($resolved);

        if ($user = $request->user()) {
            if ($user->locale !== $resolved) {
                $user->forceFill(['locale' => $resolved])->save();

                $this->audit->log(
                    'account.locale.changed',
                    $user,
                    'Preferred language changed to '.Locale::name($resolved),
                    ['locale' => $resolved],
                    $user,
                );
            }
        }

        // Returning to the page the switch was made from keeps the shopper in
        // context instead of bouncing them to the homepage.
        $back = $request->headers->get('referer');

        return redirect()->to($back && str_starts_with($back, url('/')) ? $back : route('storefront.home'));
    }
}
