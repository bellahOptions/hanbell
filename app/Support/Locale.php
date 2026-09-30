<?php

namespace App\Support;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;

/**
 * The single source of truth for which locales exist, how they are presented,
 * and how the active one is resolved.
 */
final class Locale
{
    public const SESSION_KEY = 'hanbell.locale';

    public const COOKIE_KEY = 'hanbell_locale';

    /**
     * All configured locales.
     *
     * @return array<string, array{native:string,english:string,direction:string,flag:string}>
     */
    public static function all(): array
    {
        $configured = config('hanbell.locales', []);

        $out = [];

        foreach ($configured as $code => $meta) {
            $out[$code] = [
                'native' => $meta[0] ?? strtoupper($code),
                'english' => $meta[1] ?? strtoupper($code),
                'direction' => $meta[2] ?? 'ltr',
                'flag' => $meta[3] ?? '',
            ];
        }

        return $out;
    }

    /** @return list<string> */
    public static function codes(): array
    {
        return array_keys(self::all());
    }

    public static function isSupported(?string $code): bool
    {
        return $code !== null && array_key_exists($code, self::all());
    }

    /** Normalise "en-GB" / "EN" to a supported code, or null. */
    public static function normalise(?string $code): ?string
    {
        if ($code === null || $code === '') {
            return null;
        }

        $code = strtolower(trim($code));

        if (self::isSupported($code)) {
            return $code;
        }

        $short = substr($code, 0, 2);

        return self::isSupported($short) ? $short : null;
    }

    /**
     * Resolve the locale for the current request.
     *
     * Precedence: explicit session choice → persisted cookie → the user's
     * saved preference → the browser's Accept-Language → app default.
     */
    public static function resolve(?string $userPreference = null): string
    {
        $default = self::normalise(config('app.locale')) ?? 'en';

        foreach ([
            Session::get(self::SESSION_KEY),
            request()->cookie(self::COOKIE_KEY),
            $userPreference,
            self::fromAcceptLanguage(request()?->header('Accept-Language')),
        ] as $candidate) {
            if ($normalised = self::normalise($candidate)) {
                return $normalised;
            }
        }

        return $default;
    }

    /** Apply a locale to the application for this request. */
    public static function apply(string $code): string
    {
        $code = self::normalise($code) ?? 'en';

        App::setLocale($code);
        Session::put(self::SESSION_KEY, $code);

        return $code;
    }

    /** Remember a locale for subsequent requests. */
    public static function remember(string $code): void
    {
        $code = self::normalise($code) ?? 'en';
        Session::put(self::SESSION_KEY, $code);
        cookie()->queue(cookie()->forever(self::COOKIE_KEY, $code));
    }

    public static function direction(?string $code = null): string
    {
        $code ??= App::getLocale();

        return self::all()[$code]['direction'] ?? 'ltr';
    }

    public static function isRtl(?string $code = null): bool
    {
        return self::direction($code) === 'rtl';
    }

    public static function name(?string $code = null): string
    {
        $code ??= App::getLocale();

        return self::all()[$code]['native'] ?? strtoupper((string) $code);
    }

    /**
     * Best supported match from an Accept-Language header.
     * Honours q-values so "ha;q=0.9,en;q=0.8" picks Hausa.
     */
    public static function fromAcceptLanguage(?string $header): ?string
    {
        if (! $header) {
            return null;
        }

        $candidates = [];

        foreach (explode(',', $header) as $part) {
            $bits = explode(';', trim($part));
            $tag = trim($bits[0]);
            $quality = 1.0;

            foreach (array_slice($bits, 1) as $param) {
                if (str_starts_with(trim($param), 'q=')) {
                    $quality = (float) substr(trim($param), 2);
                }
            }

            $candidates[$tag] = $quality;
        }

        arsort($candidates);

        foreach (array_keys($candidates) as $tag) {
            if ($match = self::normalise($tag)) {
                return $match;
            }
        }

        return null;
    }
}
