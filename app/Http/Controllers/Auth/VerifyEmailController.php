<?php

namespace App\Http\Controllers\Auth;

use App\Services\Security\AuditLogger;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;

/**
 * The target of the signed verification link.
 *
 * The `signed` middleware on the route already guarantees the link was issued
 * by this application and has not been altered, so an explicit signature check
 * here would be redundant.
 */
class VerifyEmailController
{
    public function __invoke(EmailVerificationRequest $request, AuditLogger $audit)
    {
        $user = $request->user();

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
            event(new Verified($user));

            $audit->emailVerified($user);
        }

        return redirect()
            ->to($user->postLoginRoute())
            ->with('toast', [
                'message' => __('hanbell.auth.verified'),
                'type' => 'success',
            ]);
    }
}