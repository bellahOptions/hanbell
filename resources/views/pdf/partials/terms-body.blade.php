@php
    $operator = $operator ?? app(\App\Services\Pdf\PdfRenderer::class)->operator();
    $brandName = $brandName ?? config('hanbell.name');
    $siteUrl = rtrim((string) config('app.url'), '/');
    $keyPages = $keyPages ?? collect();
@endphp

{{-- Preamble: who the customer is contracting with. --}}
<div class="block avoid-break" style="margin-top: 0;">
    <h3>About this document</h3>
    <p class="small" style="margin: 0 0 2mm 0;">
        <strong>{{ $brandName }}</strong> is a trading name of
        <strong>{{ $operator['name'] }}</strong>@if ($operator['registration_number']), registered in Nigeria as RC {{ $operator['registration_number'] }}@endif.
        When these terms say &ldquo;we&rdquo;, &ldquo;us&rdquo; or &ldquo;HanbellShop&rdquo;, they mean
        {{ $operator['name'] }} trading as {{ $brandName }}.
    </p>
    <p class="small" style="margin: 0;">
        These are the terms on which we provide the {{ $brandName }} marketplace and
        sell goods through it. They apply to every visit to the site and to every
        order placed through it. Please read them before you buy.
    </p>
</div>

<table class="panels avoid-break">
    <tr>
        <td>
            <p class="panel-label">Issued by</p>
            <p class="party-name">{{ $operator['name'] }}</p>
            <p class="party-meta">trading as {{ $brandName }}</p>

            @foreach ($operator['address_lines'] as $line)
                <p class="party-line">{{ $line }}</p>
            @endforeach

            @if ($operator['email'])
                <p class="party-meta">{{ $operator['email'] }}</p>
            @endif

            @if ($operator['phone'])
                <p class="party-meta">{{ $operator['phone'] }}</p>
            @endif

            @if ($operator['registration_number'])
                <p class="party-meta">RC {{ $operator['registration_number'] }}</p>
            @endif

            @if ($operator['tin'])
                <p class="party-meta">TIN {{ $operator['tin'] }}</p>
            @endif
        </td>
        <td>
            <table class="facts">
                <tr>
                    <td class="k">Document</td>
                    <td class="v">Terms of Service</td>
                </tr>
                <tr>
                    <td class="k">Version</td>
                    <td class="v">{{ $version }}</td>
                </tr>
                <tr>
                    <td class="k">Issued</td>
                    <td class="v">{{ $issueDate->format('j F Y') }}</td>
                </tr>
                <tr>
                    <td class="k">Governing law</td>
                    <td class="v">{{ ucfirst($operator['jurisdiction']) }}</td>
                </tr>
                <tr>
                    <td class="k">Website</td>
                    <td class="v">{{ parse_url($siteUrl, PHP_URL_HOST) ?: $siteUrl }}</td>
                </tr>
            </table>
        </td>
    </tr>
</table>

{{-- Summary: the CMS page's opening paragraph, which sits before any heading.
     Given its own heading here rather than left to dangle before section 1. --}}
@if (! empty($preamble))
    <div class="prose avoid-break">
        <h2>Summary</h2>
        {!! $preamble !!}
    </div>
@endif

{{-- Contents --}}
@if (! empty($sections))
    <div class="block avoid-break">
        <h3>Contents</h3>
        <table style="width: 100%; border-collapse: collapse;">
            @foreach ($sections as $index => $section)
                <tr>
                    <td style="padding: 1pt 0; font-size: 8.5pt; width: 8mm; color: {{ $colours['muted'] }};">
                        {{ $index + 1 }}.
                    </td>
                    <td style="padding: 1pt 0; font-size: 8.5pt;">
                        {{ $section['title'] }}
                    </td>
                </tr>
            @endforeach
        </table>
    </div>
@endif

{{-- The clauses, from the CMS page.

     Emitted unescaped because $sectionsHtml is already-sanitised HTML produced
     by Page::renderedContent(), which strips script/style/iframe and allow-lists
     formatting tags. Escaping it here would print the markup as visible text. --}}
<div class="prose">
    {!! $sectionsHtml !!}
</div>

{{-- Jurisdiction, which the web page states only loosely. --}}
<div class="prose avoid-break">
    <h2>{{ count($sections) + 1 }}. Governing law and jurisdiction</h2>
    <p>
        These terms and any dispute or claim arising out of them are governed by
        the laws of {{ $operator['jurisdiction'] }}. You and we both agree that the courts of
        {{ $operator['jurisdiction'] }} have exclusive jurisdiction to settle any dispute, except that
        you may also bring proceedings in the country in which you live if the law
        there gives you that right.
    </p>
    <p>
        Nothing in these terms removes or limits any statutory right you have as a
        consumer that cannot lawfully be removed or limited, including your rights
        under Nigerian consumer protection law.
    </p>

    <h2>{{ count($sections) + 2 }}. Complaints</h2>
    <p>
        If you are unhappy with anything, tell us first &mdash; most problems are
        resolved quickly. Write to
        @if ($operator['email'])
            <strong>{{ $operator['email'] }}</strong>
        @else
            our support address
        @endif
        with your order number and a description of the problem.
    </p>
    <p>
        We will acknowledge your complaint within 3 working days and give you a
        substantive response within
        <strong>{{ $operator['complaint_response_days'] }} days</strong>.
        If we cannot resolve it to your satisfaction, we will tell you what
        further options are available to you.
    </p>

    <h2>{{ count($sections) + 3 }}. Related policies</h2>
    <p>
        These terms should be read together with the policies published on our
        website, which form part of your agreement with us:
    </p>

    @if ($keyPages->isNotEmpty())
        <ul>
            @foreach ($keyPages as $page)
                <li>
                    <strong>{{ $page->title }}</strong> &mdash;
                    {{ $siteUrl }}/pages/{{ $page->slug }}
                </li>
            @endforeach
        </ul>
    @else
        <ul>
            <li><strong>Privacy Policy</strong> &mdash; {{ $siteUrl }}/pages/privacy-policy</li>
            <li><strong>Returns Policy</strong> &mdash; {{ $siteUrl }}/pages/returns-policy</li>
            <li><strong>Shipping Policy</strong> &mdash; {{ $siteUrl }}/pages/shipping-policy</li>
            <li><strong>Acceptable Use Policy</strong> &mdash; {{ $siteUrl }}/pages/acceptable-use</li>
        </ul>
    @endif
</div>

{{-- Document control: because a printed copy must be identifiable. --}}
<div class="block avoid-break">
    <h3>Document control</h3>
    <table class="facts">
        <tr>
            <td class="k">Document</td>
            <td class="v">{{ $brandName }} Terms of Service</td>
        </tr>
        <tr>
            <td class="k">Version</td>
            <td class="v">{{ $version }}</td>
        </tr>
        <tr>
            <td class="k">Issued</td>
            <td class="v">{{ $issueDate->format('j F Y') }}</td>
        </tr>
        <tr>
            <td class="k">Issued by</td>
            <td class="v">{{ $operator['name'] }}</td>
        </tr>
        @if ($operator['registration_number'])
            <tr>
                <td class="k">Registration</td>
                <td class="v">RC {{ $operator['registration_number'] }}</td>
            </tr>
        @endif
        @if ($operator['tin'])
            <tr>
                <td class="k">TIN</td>
                <td class="v">{{ $operator['tin'] }}</td>
            </tr>
        @endif
        <tr>
            <td class="k">This version effective from</td>
            <td class="v">{{ $issueDate->format('j F Y') }}</td>
        </tr>
    </table>

    <p class="small" style="margin: 3mm 0 0 0;">
        The current version of these terms is always published at
        {{ $siteUrl }}/pages/terms-of-service. The version that applies to your
        order is the one published when you placed it. If we make a significant
        change we will tell you before it takes effect.
    </p>
</div>
