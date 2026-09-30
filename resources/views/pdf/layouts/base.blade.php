<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>{{ $documentTitle ?? 'Document' }}</title>
<style>
    /*
     * Shared canvas for every HanbellShop PDF.
     *
     * dompdf supports a workable subset of CSS 2.1: no flexbox, no grid, no CSS
     * custom properties. Everything below is therefore table- and float-based
     * with literal colour values. `colours` is passed in from config but read in
     * PHP, not as a CSS variable.
     */
    @page {
        margin: {{ ($compact ?? false)
            ? '12mm 12mm 16mm 12mm'
            : (($medium ?? false) ? '15mm 14mm 16mm 14mm' : '22mm 14mm 20mm 14mm') }};
    }

    * { box-sizing: border-box; }

    body {
        font-family: "{{ $baseFont }}", sans-serif;
        font-size: {{ $baseFontSize }}pt;
        line-height: 1.45;
        color: {{ $colours['ink'] }};
        margin: 0;
        padding: 0;
    }

    /* ---------- Masthead ---------- */

    .masthead { width: 100%; border-collapse: collapse; margin-bottom: 4mm; }
    .masthead td { vertical-align: top; }

    .brand-name {
        font-size: 17pt;
        font-weight: bold;
        color: {{ $colours['brand'] }};
        letter-spacing: -0.4pt;
        margin: 0;
    }
    .brand-sub { font-size: 8pt; color: {{ $colours['muted'] }}; margin: 1pt 0 0 0; }

    /* A typographic mark, used when the logo image is unavailable. */
    .mark {
        display: inline-block;
        width: 34pt; height: 34pt;
        background-color: {{ $colours['ink'] }};
        color: {{ $colours['accent'] }};
        font-size: 16pt;
        font-weight: bold;
        text-align: center;
        line-height: 34pt;
        margin-right: 8pt;
    }
    .mark img { vertical-align: middle; }

    .doc-type {
        text-align: right;
        font-size: 19pt;
        font-weight: bold;
        letter-spacing: 1.6pt;
        text-transform: uppercase;
        color: {{ $colours['ink'] }};
        margin: 0;
    }
    .doc-ref { text-align: right; font-size: 8.5pt; color: {{ $colours['muted'] }}; margin: 2pt 0 0 0; }

    /* Brand rule: green over yellow, echoing the logo lockup. */
    .rule-brand { height: 3pt; background-color: {{ $colours['brand'] }}; margin: 0 0 1pt 0; }
    .rule-accent { height: 1.6pt; background-color: {{ $colours['accent'] }}; margin: 0 0 6mm 0; }

    /* ---------- Meta / parties ---------- */

    .panels { width: 100%; border-collapse: collapse; margin-bottom: 5mm; }
    .panels > tbody > tr > td { vertical-align: top; width: 50%; padding-right: 6mm; }
    .panels > tbody > tr > td:last-child { padding-right: 0; }

    .panel-label {
        font-size: 7pt;
        font-weight: bold;
        letter-spacing: 0.9pt;
        text-transform: uppercase;
        color: {{ $colours['muted'] }};
        margin: 0 0 1.5pt 0;
    }
    .party-name { font-size: 10.5pt; font-weight: bold; margin: 0 0 1.5pt 0; }
    .party-line { margin: 0; font-size: 8.5pt; color: #3f3f3a; }
    .party-meta { margin: 0; font-size: 8pt; color: {{ $colours['muted'] }}; }

    .facts { width: 100%; border-collapse: collapse; }
    .facts td { padding: 1.6pt 0; font-size: 8.5pt; vertical-align: top; }
    .facts td.k { color: {{ $colours['muted'] }}; width: 45%; }
    .facts td.v { text-align: right; font-weight: bold; }

    /* ---------- Line items ---------- */

    table.items { width: 100%; border-collapse: collapse; margin-top: 1mm; }
    table.items thead th {
        background-color: {{ $colours['surface'] }};
        border-bottom: 1pt solid {{ $colours['rule'] }};
        border-top: 1pt solid {{ $colours['rule'] }};
        padding: 4pt 5pt;
        font-size: 7.5pt;
        letter-spacing: 0.5pt;
        text-transform: uppercase;
        color: {{ $colours['muted'] }};
        text-align: left;
    }
    table.items thead th.num { text-align: right; }
    table.items tbody td {
        padding: 5pt;
        border-bottom: 0.6pt solid {{ $colours['rule'] }};
        vertical-align: top;
    }
    table.items tbody td.num { text-align: right; white-space: nowrap; }
    .item-name { font-weight: bold; font-size: 9pt; }
    .item-meta { font-size: 7.5pt; color: {{ $colours['muted'] }}; margin-top: 1pt; }
    .item-vendor {
        font-size: 6.8pt;
        text-transform: uppercase;
        letter-spacing: 0.5pt;
        color: {{ $colours['brand'] }};
        font-weight: bold;
    }

    /* A vendor grouping header inside the items table. */
    tr.vendor-head td {
        background-color: #ffffff;
        border-bottom: 0.6pt solid {{ $colours['rule'] }};
        padding: 7pt 5pt 2pt 5pt;
    }
    .vendor-title {
        font-size: 8pt;
        font-weight: bold;
        text-transform: uppercase;
        letter-spacing: 0.7pt;
        color: {{ $colours['ink'] }};
    }

    /* ---------- Totals ---------- */

    .totals-wrap { width: 100%; border-collapse: collapse; margin-top: 4mm; }
    .totals-wrap > tbody > tr > td { vertical-align: top; }
    td.totals-cell { width: 58mm; }

    table.totals { width: 100%; border-collapse: collapse; }
    table.totals td { padding: 3pt 0; font-size: 9pt; }
    table.totals td.k { color: #3f3f3a; }
    table.totals td.v { text-align: right; font-weight: bold; white-space: nowrap; }
    table.totals tr.grand td {
        border-top: 1.4pt solid {{ $colours['ink'] }};
        padding-top: 5pt;
        font-size: 12pt;
        font-weight: bold;
    }
    table.totals tr.balance td {
        border-top: 0.8pt solid {{ $colours['rule'] }};
        color: {{ $colours['brand'] }};
        font-weight: bold;
        font-size: 10pt;
        padding-top: 4pt;
    }
    .discount-v { color: {{ $colours['brand'] }}; }

    /* ---------- Blocks ---------- */

    .block {
        border: 0.8pt solid {{ $colours['rule'] }};
        background-color: {{ $colours['surface'] }};
        padding: 4mm;
        margin-top: 5mm;
    }
    .block h3 {
        font-size: 8pt;
        text-transform: uppercase;
        letter-spacing: 0.8pt;
        margin: 0 0 2.5mm 0;
        color: {{ $colours['muted'] }};
    }

    .badge {
        display: inline-block;
        padding: 2.5pt 7pt;
        font-size: 7.5pt;
        font-weight: bold;
        letter-spacing: 0.7pt;
        text-transform: uppercase;
        border: 0.8pt solid {{ $colours['brand'] }};
        color: {{ $colours['brand'] }};
    }
    .badge.due { border-color: #b45309; color: #b45309; }
    .badge.void { border-color: #b91c1c; color: #b91c1c; }

    .paid-stamp {
        display: inline-block;
        border: 2pt solid {{ $colours['brand'] }};
        color: {{ $colours['brand'] }};
        font-size: 15pt;
        font-weight: bold;
        letter-spacing: 2.5pt;
        text-transform: uppercase;
        padding: 4pt 12pt;
        margin-top: 3mm;
    }

    /* ---------- Prose (terms, notes) ---------- */

    .prose { font-size: 9pt; color: #262623; }
    .prose h2 {
        font-size: 10.5pt;
        margin: 6mm 0 2mm 0;
        color: {{ $colours['ink'] }};
        border-bottom: 0.6pt solid {{ $colours['rule'] }};
        padding-bottom: 1.5mm;
    }
    .prose h3 { font-size: 9.5pt; margin: 4mm 0 1.5mm 0; }
    .prose p { margin: 0 0 2.5mm 0; text-align: justify; }
    .prose ul, .prose ol { margin: 0 0 3mm 0; padding-left: 6mm; }
    .prose li { margin-bottom: 1.4mm; }
    .prose a { color: {{ $colours['brand'] }}; text-decoration: none; }
    .prose table { width: 100%; border-collapse: collapse; margin: 0 0 3mm 0; }
    .prose th, .prose td {
        border: 0.6pt solid {{ $colours['rule'] }};
        padding: 3pt 5pt;
        font-size: 8.5pt;
        text-align: left;
    }
    .prose th { background-color: {{ $colours['surface'] }}; }
    .prose strong { color: {{ $colours['ink'] }}; }

    /* ---------- Misc ---------- */

    .note { font-size: 8pt; color: {{ $colours['muted'] }}; margin-top: 2mm; }
    .small { font-size: 7.5pt; color: {{ $colours['muted'] }}; }
    .nowrap { white-space: nowrap; }
    .right { text-align: right; }
    .center { text-align: center; }
    .spacer { height: 5mm; }
    .page-break { page-break-before: always; }
    .avoid-break { page-break-inside: avoid; }

    /*
     * Compact variant.
     *
     * A receipt is issued on A5 and must land on a single sheet — a two-page
     * receipt is awkward to print, hand over or keep. Tightening the spacing on
     * the body rather than the whole stylesheet keeps the invoice's more
     * generous rhythm intact.
     */
    body.compact { font-size: 8.5pt; line-height: 1.35; }
    body.compact .rule-accent { margin-bottom: 4mm; }
    body.compact .panels { margin-bottom: 3.5mm; }
    body.compact .facts td { padding: 1pt 0; }
    body.compact .block { padding: 3mm; margin-top: 3.5mm; }
    body.compact table.items thead th { padding: 3pt 4pt; }
    body.compact table.items tbody td { padding: 3.5pt 4pt; }
    body.compact table.totals td { padding: 1.8pt 0; }
    body.compact table.totals tr.grand td { font-size: 11pt; padding-top: 3.5pt; }
    body.compact .paid-stamp { font-size: 13pt; padding: 3pt 9pt; margin-top: 2mm; }
    body.compact .note { margin-top: 1.5mm; }

    /*
     * Medium variant.
     *
     * The invoice carries more than a receipt — parties, an item table, a status
     * block, totals and notes — and at the default rhythm it spilled a few
     * millimetres onto a second sheet, leaving a page holding nothing but the
     * notes. Trimming the margins and the inter-block spacing is what brings it
     * back to one page; type sizes are left alone so it still reads as a formal
     * document rather than a cramped one.
     */
    body.medium .masthead { margin-bottom: 3mm; }
    body.medium .rule-accent { margin-bottom: 4mm; }
    body.medium .panels { margin-bottom: 3.5mm; }
    body.medium .block { padding: 3.5mm; margin-top: 3.5mm; }
    body.medium table.items tbody td { padding: 4pt 5pt; }
    body.medium table.totals td { padding: 2.4pt 0; }
</style>
</head>
<body class="{{ ($compact ?? false) ? 'compact' : (($medium ?? false) ? 'medium' : '') }}">

{{-- Masthead --}}
<table class="masthead">
    <tr>
        <td style="width: 55%;">
            @if ($logoDataUri ?? null)
                <img src="{{ $logoDataUri }}" alt="{{ $brandName }}" style="height: 30pt;">
            @else
                <span class="mark">H</span>
                <span class="brand-name">{{ $brandName }}</span>
            @endif
            <p class="brand-sub">
                A product of {{ $operator['name'] }}@if ($operator['registration_number']) &nbsp;·&nbsp; RC {{ $operator['registration_number'] }}@endif
            </p>
        </td>
        <td style="width: 45%;">
            <p class="doc-type">{{ $documentType ?? 'Document' }}</p>
            @if ($documentReference ?? null)
                <p class="doc-ref">{{ $documentReference }}</p>
            @endif
            @if (($documentStatus ?? null))
                <p class="doc-ref">{{ $documentStatus }}</p>
            @endif
        </td>
    </tr>
</table>
<div class="rule-brand"></div>
<div class="rule-accent"></div>

{{--
    The body is already-rendered HTML from a sibling partial, so it must be
    emitted unescaped. Using {{ $slot }} here escapes the markup and prints the
    document's own tags as visible text — which is exactly what it did before
    this was caught by rendering the invoice and looking at it.
--}}
{!! $slot ?? '' !!}

</body>
</html>
