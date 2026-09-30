<?php

namespace App\Http\Controllers\Advertising;

use App\Models\AdCreative;
use App\Models\AdPlacement;
use App\Services\Advertising\AdDestination;
use App\Services\Advertising\AdTrackingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ad impression beacon and click-through redirect.
 *
 * Impressions arrive from `navigator.sendBeacon` once a creative has been at
 * least half visible for a full second (see resources/js/app.js). Counting at
 * render time would bill advertisers for ads nobody scrolled to.
 *
 * Clicks are validated, not trusted: the destination is re-checked against the
 * allow-list here as well as at save time, so a host removed from the list stops
 * working immediately and an unvalidated destination can never become an open
 * redirect.
 */
class AdTrackingController
{
    public function __construct(
        private readonly AdTrackingService $tracking,
        private readonly AdDestination $destination,
    ) {}

    public function impression(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'creative' => ['required', 'string', 'max:255'],
            'placement' => ['nullable', 'string', 'max:64'],
        ]);

        // The creative is identified by its signed token, so a forged id cannot
        // inflate another advertiser's delivery.
        $creative = AdCreative::query()
            ->where('uuid', $this->uuidFromToken($validated['creative'], 'ad_creative'))
            ->first();

        if (! $creative) {
            return response()->json(['counted' => false], 202);
        }

        $placement = isset($validated['placement'])
            ? AdPlacement::where('key', $validated['placement'])->first()
            : null;

        $counted = $this->tracking->recordImpression(
            $creative,
            $placement,
            $request->ip(),
            $request->userAgent(),
            $request->user()?->id,
        );

        return response()->json(['counted' => $counted], 202);
    }

    public function click(Request $request, string $creative): Response
    {
        // A click costs the advertiser real money, so it is rate-limited
        // independently of the impression endpoint.
        $key = 'ad:click:'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 30)) {
            abort(429, 'Too many requests.');
        }

        RateLimiter::hit($key, 60);

        $model = AdCreative::query()
            ->where('uuid', $this->uuidFromToken($creative, 'ad_creative'))
            ->with('campaign')
            ->first();

        if (! $model || ! $model->campaign) {
            abort(404);
        }

        $destination = $model->destination_url;

        // Re-validate at click time. If the host was removed from the
        // allow-list, the click must not follow.
        $resolved = $this->destination->resolve($destination);

        if ($resolved === null) {
            Log::warning('Ad click blocked: destination failed validation.', [
                'creative' => $model->uuid,
                'campaign' => $model->campaign->name,
                'destination' => $destination,
                'reason' => $this->destination->rejectionReason($destination),
            ]);

            abort(404);
        }

        $placement = $request->integer('placement')
            ? AdPlacement::find($request->integer('placement'))
            : null;

        $this->tracking->recordClick(
            $model,
            $placement,
            $request->ip(),
            $request->userAgent(),
            $request->user()?->id,
        );

        // nofollow + noopener: a paid link must not pass ranking equity, and the
        // destination must not get a handle on this window.
        return redirect()->away($resolved, 302, [
            'X-Robots-Tag' => 'noindex, nofollow',
        ]);
    }

    /**
     * Decode a signed ad token into a uuid, or return a value that matches
     * nothing so the request quietly does nothing.
     */
    private function uuidFromToken(string $token, string $type): string
    {
        try {
            return \App\Support\Tokens\UrlToken::decode($token, $type)['uuid'];
        } catch (\App\Support\Tokens\InvalidUrlTokenException) {
            return '00000000-0000-0000-0000-000000000000';
        }
    }
}
