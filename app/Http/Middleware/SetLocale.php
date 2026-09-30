<?php

namespace App\Http\Middleware;

use App\Support\Locale;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves and applies the active locale for the request.
 *
 * Precedence lives in App\Support\Locale so the CLI, the locale switcher and
 * this middleware all agree on how a language is chosen.
 */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $userLocale = $request->user()?->locale;

        Locale::apply(Locale::resolve($userLocale));

        $response = $next($request);

        // Tell caches and crawlers that the same URL can return different
        // languages, which is what the hreflang tags advertise.
        $response->headers->set('Content-Language', app()->getLocale());

        if (method_exists($response, 'header')) {
            $response->header('Vary', $this->mergeVary($response->headers->get('Vary'), 'Accept-Language'));
        }

        return $response;
    }

    private function mergeVary(?string $current, string $add): string
    {
        $values = collect(explode(',', (string) $current))
            ->map(fn (string $value) => trim($value))
            ->filter()
            ->push($add)
            ->unique()
            ->implode(', ');

        return $values;
    }
}
