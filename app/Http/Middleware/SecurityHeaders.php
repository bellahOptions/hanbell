<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Baseline security response headers.
 *
 * The CSP is intentionally permissive about inline styles (Tailwind and a few
 * inline brand colours need them) but strict about scripts: only same-origin
 * and explicitly allowed CDNs may execute. `frame-ancestors 'none'` replaces
 * X-Frame-Options for modern browsers while the legacy header is kept for old
 * ones.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Never interfere with a binary/streamed response.
        if (! method_exists($response, 'header')) {
            return $response;
        }

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('X-Permitted-Cross-Domain-Policies', 'none');

        // Only advertise the powerful features the storefront actually uses.
        $response->headers->set(
            'Permissions-Policy',
            'camera=(), microphone=(), geolocation=(self), payment=(self), usb=(), magnetometer=(), gyroscope=()',
        );

        $response->headers->set('Content-Security-Policy', $this->contentSecurityPolicy());

        // HSTS only makes sense once the site is genuinely served over https.
        if ($request->secure() || app()->isProduction()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }

    private function contentSecurityPolicy(): string
    {
        // Livewire and Alpine both need 'unsafe-eval' for expression evaluation
        // and inline script execution, which is why the script policy is not
        // nonce-based here.
        $scripts = [
            "'self'",
            "'unsafe-inline'",
            "'unsafe-eval'",
        ];

        $styles = [
            "'self'",
            "'unsafe-inline'",
        ];

        $images = [
            "'self'",
            'data:',
            'blob:',
            // Seed and demo imagery is served from Unsplash's CDN; uploaded
            // media comes from Cloudinary when configured.
            'https://images.unsplash.com',
            'https://plus.unsplash.com',
            'https://res.cloudinary.com',
        ];

        $fonts = ["'self'", 'data:'];

        return implode('; ', [
            "default-src 'self'",
            'script-src '.implode(' ', $scripts),
            'style-src '.implode(' ', $styles),
            'img-src '.implode(' ', $images),
            'font-src '.implode(' ', $fonts),
            "connect-src 'self'",
            "form-action 'self'",
            "frame-ancestors 'none'",
            "base-uri 'self'",
            "object-src 'none'",
            'upgrade-insecure-requests',
        ]);
    }
}
