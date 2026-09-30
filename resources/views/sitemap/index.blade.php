<?xml version="1.0" encoding="UTF-8"?>
{{--
    Sitemap index.

    Split by section rather than emitted as one file: a single sitemap
    containing every product does not scale, and Search Console reports coverage
    far more usefully when product, category, vendor and page URLs are separable.
--}}
<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
@foreach ($sections as $section)
    <sitemap>
        <loc>{{ $section['loc'] }}</loc>
        <lastmod>{{ $section['lastmod'] }}</lastmod>
    </sitemap>
@endforeach
</sitemapindex>
