<?php

namespace App\Services\Pdf;

use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\View;
use RuntimeException;

/**
 * Renders PDFs from Blade views.
 *
 * Wraps the dompdf engine directly rather than a Laravel wrapper package — the
 * official wrapper caps at Laravel 11, and this indirection is small enough that
 * swapping engines later means changing one class.
 *
 * Two deliberate constraints:
 *
 *  1. Remote images are disabled. A document must not hang or leak a request to
 *     a third-party host because a template referenced one; anything that needs
 *     an image embeds it from disk through the chroot.
 *
 *  2. The font is DejaVu Sans because it genuinely covers what HanbellShop
 *     prints. That was verified by rendering and reading the bytes back: the
 *     Naira sign (U+20A6) and combining diacritics in names like "Ọ̀rẹ́ Studio"
 *     survive with no substitution characters. If the font is ever changed,
 *     re-run that check — a missing glyph degrades to a silent tofu box.
 */
class PdfRenderer
{
    /**
     * Render a Blade view to raw PDF bytes.
     *
     * @param  array<string,mixed>  $data
     */
    public function render(string $view, array $data = [], ?string $paper = null, string $orientation = 'portrait'): string
    {
        $dompdf = $this->makeDompdf();

        $paper ??= (string) config('hanbell.pdf.paper', 'a4');

        /*
         * A5 documents are compacted automatically.
         *
         * The only A5 document is the receipt, and a receipt that spills onto a
         * second sheet is awkward to print and hand over — so the tighter
         * spacing is tied to the paper rather than left to each template to
         * remember.
         */
        $data['compact'] ??= in_array(strtolower($paper), ['a5', 'a5-landscape'], true);

        // Render the Blade view first so a template error surfaces as a normal
        // exception rather than a half-written PDF.
        $html = View::make($view, $data + $this->sharedData())->render();

        $this->applyPageNumbers($dompdf);

        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper($paper, $orientation);
        $dompdf->render();

        $output = $dompdf->output();

        if ($output === '' || ! str_starts_with($output, '%PDF')) {
            throw new RuntimeException("PDF rendering of [{$view}] did not produce a valid document.");
        }

        return $output;
    }

    /**
     * Render to a file on the configured disk.
     *
     * @param  array<string,mixed>  $data
     * @return array{path:string,disk:string,bytes:int}
     */
    public function store(string $view, string $path, array $data = [], ?string $paper = null): array
    {
        $bytes = $this->render($view, $data, $paper);
        $disk = (string) config('hanbell.pdf.disk', 'local');

        \Illuminate\Support\Facades\Storage::disk($disk)->put($path, $bytes);

        return ['path' => $path, 'disk' => $disk, 'bytes' => strlen($bytes)];
    }

    /**
     * An HTTP response that streams the document.
     *
     * `inline` is the default so a customer can read an invoice in a browser tab
     * without downloading it; pass inline: false for an explicit download.
     *
     * `cacheable` is false for anything tied to a person or an order — a
     * financial document must never sit in a shared proxy. The published terms
     * are the one exception, and even then only briefly.
     */
    public function response(
        string $view,
        string $filename,
        array $data = [],
        bool $inline = true,
        ?string $paper = null,
        bool $cacheable = false,
    ): \Illuminate\Http\Response {
        $bytes = $this->render($view, $data, $paper);

        return response($bytes, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf(
                '%s; filename="%s"',
                $inline ? 'inline' : 'attachment',
                $this->safeFilename($filename),
            ),
            'Content-Length' => (string) strlen($bytes),
            'Cache-Control' => $cacheable
                ? 'public, max-age=3600'
                : 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /**
     * Data every template expects, so an individual template cannot forget it
     * and produce a document with a missing issuer.
     *
     * @return array<string,mixed>
     */
    public function sharedData(): array
    {
        return [
            'operator' => $this->operator(),
            'colours' => config('hanbell.pdf.colours', []),
            'brandName' => config('hanbell.name'),
            'baseFont' => config('hanbell.pdf.font', 'DejaVu Sans'),
            'baseFontSize' => config('hanbell.pdf.base_font_size', 9.5),
            'logoDataUri' => $this->markDataUri(),
            'generatedAt' => now(),
        ];
    }

    /**
     * The legal entity behind the brand, with blank fields dropped so a document
     * never prints "RC: " with nothing after it.
     *
     * @return array<string,mixed>
     */
    public function operator(): array
    {
        $operator = config('hanbell.operator', []);

        $address = array_filter([
            $operator['address_line'] ?? null,
            $operator['city'] ?? null,
            $operator['state'] ?? null,
            ($operator['country'] ?? 'NG') === 'NG' ? 'Nigeria' : ($operator['country'] ?? null),
        ]);

        return [
            'name' => $operator['name'] ?? config('hanbell.name'),
            'trading_name' => $operator['trading_name'] ?? config('hanbell.name'),
            'registration_number' => $this->clean($operator['registration_number'] ?? null),
            'tin' => $this->clean($operator['tin'] ?? null),
            'vat_number' => $this->clean($operator['vat_number'] ?? null),
            'address_lines' => array_values($address),
            'email' => $this->clean($operator['email'] ?? null),
            'phone' => $this->clean($operator['phone'] ?? null),
            'website' => $this->clean($operator['website'] ?? null),
            'jurisdiction' => $operator['jurisdiction'] ?? 'the Federal Republic of Nigeria',
            'complaint_response_days' => (int) ($operator['complaint_response_days'] ?? 30),
        ];
    }

    /**
     * Absolute path to the brand logo, for embedding.
     *
     * Falls back to the knockout logo on dark artwork, and returns null when
     * neither exists so a template can degrade to a typographic mark.
     */
    public function logoPath(bool $light = false): ?string
    {
        $candidates = $light
            ? ['images/logo-wt.svg', 'images/logo.svg']
            : ['images/logo.svg', 'images/logo-wt.svg'];

        foreach ($candidates as $candidate) {
            $path = public_path($candidate);

            if (is_file($path)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * Path to the square brand mark, for embedding in a PDF.
     *
     * The brand's primary artwork (`images/logo.svg`) is a wordmark, and dompdf
     * cannot rasterise SVG — it silently embeds nothing. The generated
     * application icons are real PNGs of the same bell device on the brand
     * green, so one of those is used instead. That was checked by rendering and
     * counting embedded image XObjects; an SVG here yields zero, which is why
     * this deliberately does not point at the wordmark.
     */
    public function markPath(int $preferSize = 192): ?string
    {
        $candidates = [
            "android-icon-{$preferSize}x{$preferSize}.png",
            'android-icon-144x144.png',
            'android-icon-192x192.png',
            'apple-icon-180x180.png',
            'ms-icon-310x310.png',
        ];

        foreach ($candidates as $candidate) {
            $path = public_path($candidate);

            if (is_file($path)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * A data URI for the brand mark.
     *
     * Embedded as a data URI so dompdf's chroot and remote-image rules cannot
     * interfere, and so the document is self-contained.
     */
    public function markDataUri(int $preferSize = 192): ?string
    {
        $path = $this->markPath($preferSize);

        if ($path === null) {
            return null;
        }

        return 'data:image/png;base64,'.base64_encode((string) file_get_contents($path));
    }

    /**
     * The wordmark, only useful where a template can fall back to text.
     *
     * Retained for templates that want to reference the primary artwork even
     * though dompdf will not render it.
     */
    public function logoDataUri(bool $light = false): ?string
    {
        $path = $this->logoPath($light);

        if ($path === null) {
            return null;
        }

        // SVG is not rasterised by dompdf, so only report a raster logo.
        if (str_ends_with($path, '.svg')) {
            return null;
        }

        return 'data:image/png;base64,'.base64_encode((string) file_get_contents($path));
    }

    /* ------------------------------------------------------------------ *
     * Internals
     * ------------------------------------------------------------------ */

    private function makeDompdf(): Dompdf
    {
        $fontDir = (string) config('hanbell.pdf.font_dir');

        foreach ([$fontDir, $fontDir.'/cache'] as $dir) {
            if (! is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
        }

        $options = new Options;
        $options->setDefaultFont((string) config('hanbell.pdf.font', 'DejaVu Sans'));
        $options->setIsRemoteEnabled((bool) config('hanbell.pdf.remote_images', false));
        $options->setIsHtml5ParserEnabled(true);
        $options->setFontDir($fontDir);
        $options->setFontCache($fontDir);
        $options->setTempDir(sys_get_temp_dir());
        // Confine file access to the project so a template cannot read elsewhere.
        $options->setChroot(base_path());
        $options->setDpi(96);

        return new Dompdf($options);
    }

    /**
     * Stamp "Page n of m" and the issuer line on every page.
     *
     * Done through dompdf's page_script rather than a CSS @page footer because
     * the running page count is only known after layout.
     */
    private function applyPageNumbers(Dompdf $dompdf): void
    {
        $operator = $this->operator();
        $brand = (string) config('hanbell.name');

        $dompdf->setCallbacks([
            'end_document' => function ($pageNumber) use ($dompdf, $operator, $brand): void {
                $canvas = $dompdf->getCanvas();
                $width = $canvas->get_width();
                $height = $canvas->get_height();
                $total = $canvas->get_page_count();

                $font = (string) config('hanbell.pdf.font', 'DejaVu Sans');
                $muted = $this->hexToRgb((string) config('hanbell.pdf.colours.muted', '#74746c'));

                $canvas->page_text(36, $height - 44, $brand.' — a product of '.$operator['name'], $font, 7.5, $muted);
                $canvas->page_text(
                    $width - 150,
                    $height - 44,
                    sprintf('Page %d of %d', $pageNumber, $total),
                    $font,
                    7.5,
                    $muted,
                );
            },
        ]);
    }

    /** @return array{0:float,1:float,2:float} */
    private function hexToRgb(string $hex): array
    {
        $hex = ltrim($hex, '#');

        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }

        return [
            hexdec(substr($hex, 0, 2)) / 255,
            hexdec(substr($hex, 2, 2)) / 255,
            hexdec(substr($hex, 4, 2)) / 255,
        ];
    }

    private function clean(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    /**
     * Strip anything that could break a Content-Disposition header.
     */
    private function safeFilename(string $filename): string
    {
        $filename = preg_replace('/[^A-Za-z0-9._-]+/', '-', $filename) ?? 'document.pdf';

        return str_ends_with($filename, '.pdf') ? $filename : $filename.'.pdf';
    }
}
