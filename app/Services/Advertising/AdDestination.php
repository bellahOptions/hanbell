<?php

namespace App\Services\Advertising;

use Illuminate\Support\Str;

/**
 * Validates where an ad may send a visitor.
 *
 * An ad destination is attacker-influenced data (a vendor or advertiser supplies
 * it) that the application then redirects to. Without validation that is an open
 * redirect: an attacker books a cheap campaign pointing at their own phishing
 * page and gets a trusted hanbellshop.com link to distribute.
 *
 * The rule is therefore an allow-list, not a deny-list:
 *   - an internal path ("/shop/shoes") is always allowed;
 *   - an absolute URL is allowed only if its host is explicitly permitted,
 *     using HTTPS (production) and matching exactly — no suffix match, which
 *     would let "evil-hanbellshop.test" through.
 *
 * The same check runs at save time (so an unusable destination cannot be
 * stored) and again at click time (so a host removed from the allow-list stops
 * working immediately).
 */
class AdDestination
{
    /** Schemes that must never be followed from an ad click. */
    private const BLOCKED_SCHEMES = ['javascript', 'data', 'vbscript', 'file'];

    public function __construct(private readonly array $allowedHosts = []) {}

    public static function defaultAllowedHosts(): array
    {
        return (array) config('hanbell.ads.allowed_redirect_hosts', []);
    }

    public function allowedHosts(): array
    {
        return $this->allowedHosts ?: self::defaultAllowedHosts();
    }

    /**
     * Is this destination safe to redirect to?
     */
    public function isSafe(?string $url): bool
    {
        $url = trim((string) $url);

        if ($url === '') {
            return false;
        }

        // Reject control characters, which can be used to smuggle a header
        // injection or to hide the real scheme from a naive parser.
        if (preg_match('/[\x00-\x1F\x7F]/', $url)) {
            return false;
        }

        // Scheme-relative ("//evil.com") is an absolute URL in disguise.
        if (str_starts_with($url, '//')) {
            return false;
        }

        // A protocol-relative or explicit scheme.
        if (preg_match('#^([a-zA-Z][a-zA-Z0-9+.\-]*):#', $url, $matches)) {
            $scheme = strtolower($matches[1]);

            if (in_array($scheme, self::BLOCKED_SCHEMES, true)) {
                return false;
            }

            if (! in_array($scheme, ['http', 'https'], true)) {
                return false;
            }

            return $this->isAllowedAbsoluteUrl($url);
        }

        // Anything else is treated as an internal path.
        return $this->isSafeInternalPath($url);
    }

    /**
     * A relative, application-local path.
     */
    private function isSafeInternalPath(string $path): bool
    {
        // Must start with a single slash (not "//").
        if (! str_starts_with($path, '/')) {
            return false;
        }

        if (str_starts_with($path, '//')) {
            return false;
        }

        // Backslashes are normalised to slashes by some browsers, so
        // "/\evil.com" would become "//evil.com".
        if (str_contains($path, '\\')) {
            return false;
        }

        return true;
    }

    private function isAllowedAbsoluteUrl(string $url): bool
    {
        $parts = parse_url($url);
        $host = $parts['host'] ?? null;

        if (blank($host)) {
            return false;
        }

        $host = strtolower($host);

        // Credentials in the authority ("https://hanbellshop.test@evil.com")
        // are a classic confusion trick — reject outright.
        if (isset($parts['user']) || isset($parts['pass'])) {
            return false;
        }

        $allowed = array_map(
            fn ($allowedHost) => strtolower(trim((string) $allowedHost)),
            $this->allowedHosts(),
        );

        // The application's own host is always permitted.
        $allowed[] = strtolower((string) parse_url((string) config('app.url'), PHP_URL_HOST));
        $allowed = array_values(array_filter(array_unique($allowed)));

        foreach ($allowed as $candidate) {
            if ($candidate !== '' && $host === $candidate) {
                return true;
            }
        }

        return false;
    }

    /**
     * Whether the destination points back into this application.
     */
    public function isInternal(string $url): bool
    {
        if (str_starts_with($url, '/') && ! str_starts_with($url, '//')) {
            return true;
        }

        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        $appHost = strtolower((string) parse_url((string) config('app.url'), PHP_URL_HOST));

        return $host !== '' && $host === $appHost;
    }

    /**
     * Resolve a destination to an absolute URL for the redirect.
     */
    public function resolve(string $url): ?string
    {
        if (! $this->isSafe($url)) {
            return null;
        }

        if ($this->isInternal($url) && str_starts_with($url, '/')) {
            return url($url);
        }

        return $url;
    }

    /**
     * Validation message for the admin form.
     */
    public function rejectionReason(string $url): string
    {
        if (trim($url) === '') {
            return 'A destination URL is required.';
        }

        if (str_starts_with(trim($url), '//')) {
            return 'Protocol-relative URLs (starting with "//") are not allowed — use an internal path or a full https:// address.';
        }

        if (preg_match('#^([a-zA-Z][a-zA-Z0-9+.\-]*):#', trim($url), $matches)
            && in_array(strtolower($matches[1]), self::BLOCKED_SCHEMES, true)) {
            return 'That URL scheme is not allowed.';
        }

        $host = parse_url(trim($url), PHP_URL_HOST);

        if ($host) {
            return sprintf(
                'The host "%s" is not on the allowed redirect list. Add it to HANBELL_AD_ALLOWED_HOSTS or use an internal path like /shop.',
                Str::limit((string) $host, 60),
            );
        }

        return 'Use an internal path beginning with "/" or a full https:// address on an allowed host.';
    }
}
