@php
    use App\Support\Seo;

    /**
     * Everything that belongs in <head> and nowhere else.
     *
     * All of it comes from one Seo object so Open Graph, Twitter, canonical,
     * hreflang and JSON-LD can never disagree with each other about what this
     * page is.
     *
     * @var Seo $seo
     */
    $seo ??= app(Seo::class);
    $metaTitle = $seo->resolvedTitle();
    $metaDescription = $seo->resolvedDescription();
    $metaImage = $seo->resolvedImage();
@endphp

<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="csrf-token" content="{{ csrf_token() }}">

<title>{{ $metaTitle }}</title>
<meta name="description" content="{{ $metaDescription }}">

@if ($keywords = $seo->resolvedKeywords())
    <meta name="keywords" content="{{ implode(', ', $keywords) }}">
@endif

<meta name="robots" content="{{ $seo->resolvedRobots() }}">
<link rel="canonical" href="{{ $seo->resolvedCanonical() }}">

{{-- Language alternates. Every locale is served from the same path, so the
     alternates carry an explicit ?hl= parameter. --}}
@foreach ($seo->alternates() as $alternate)
    <link rel="alternate" hreflang="{{ $alternate['hreflang'] }}" href="{{ $alternate['href'] }}">
@endforeach

{{-- Open Graph --}}
<meta property="og:type" content="{{ $seo->resolvedType() }}">
<meta property="og:site_name" content="{{ config('hanbell.name') }}">
<meta property="og:title" content="{{ $seo->openGraphTitle() }}">
<meta property="og:description" content="{{ $seo->openGraphDescription() }}">
<meta property="og:url" content="{{ $seo->resolvedCanonical() }}">
<meta property="og:locale" content="{{ str_replace('-', '_', app()->getLocale()) }}">
@foreach (array_diff(array_keys(\App\Support\Locale::all()), [app()->getLocale()]) as $otherLocale)
    <meta property="og:locale:alternate" content="{{ str_replace('-', '_', $otherLocale) }}">
@endforeach
@if ($metaImage)
    <meta property="og:image" content="{{ $metaImage }}">
    <meta property="og:image:alt" content="{{ $metaTitle }}">
@endif

{{-- Twitter / X --}}
<meta name="twitter:card" content="{{ $seo->twitterCard() }}">
<meta name="twitter:title" content="{{ $seo->openGraphTitle() }}">
<meta name="twitter:description" content="{{ $seo->openGraphDescription() }}">
@if ($metaImage)
    <meta name="twitter:image" content="{{ $metaImage }}">
@endif
@if ($handle = config('hanbell.seo.twitter_handle'))
    <meta name="twitter:site" content="{{ $handle }}">
@endif

{{-- Brand --}}
<meta name="theme-color" content="#178508">
<meta name="color-scheme" content="light">
<meta name="apple-mobile-web-app-title" content="{{ config('hanbell.name') }}">

{{-- Icons / PWA. These are the real generated asset set. --}}
<link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
<link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
<link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
<link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-icon-180x180.png') }}">
<link rel="manifest" href="{{ asset('manifest.json') }}">

{{-- Structured data. A single @graph keeps entity references (Organization,
     WebSite, Product, BreadcrumbList) consistent across the site. --}}

@if ($jsonLd = $seo->toJsonLd())
    <script type="application/ld+json">{!! $jsonLd !!}</script>
@endif

@stack('seo')
