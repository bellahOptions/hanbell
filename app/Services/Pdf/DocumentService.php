<?php

namespace App\Services\Pdf;

use App\Models\Order;
use App\Models\Page;
use App\Models\VendorOrder;
use App\Services\Pdf\Data\DocumentLine;
use App\Services\Pdf\Data\DocumentParty;
use App\Services\Pdf\Data\DocumentTotals;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;

/**
 * Builds and serves every PDF document in the suite.
 *
 * Each public method returns a Response ready to send, so a controller is only
 * responsible for authorisation — the document's shape and its numbering scheme
 * live here.
 *
 * NUMBERING
 *
 * Numbers are derived from the order, not stored in a counter table. That is
 * deliberate: a derived number is reproducible, so re-downloading a six-month-old
 * invoice produces the identical document rather than one that depends on how
 * many invoices happened to be issued before it. An invoice for order
 * HB-2026-000042 is INV-HB-2026-000042, every time, and a receipt for the same
 * order is RCP-HB-2026-000042.
 */
class DocumentService
{
    public function __construct(
        private readonly PdfRenderer $renderer,
        private readonly DocumentFactory $factory,
    ) {}

    /* ------------------------------------------------------------------ *
     * Invoice
     * ------------------------------------------------------------------ */

    /**
     * A tax invoice for an order.
     *
     * Only reachable once the order is paid: issuing an invoice for an unpaid
     * order invites the customer to pay against a document we would then have to
     * void.
     */
    public function invoice(Order $order, bool $inline = true): Response
    {
        $order->loadMissing(['items', 'payments', 'coupon']);

        return $this->renderer->response(
            view: 'pdf.invoice',
            filename: 'invoice-'.$order->number.'.pdf',
            data: $this->invoiceData($order),
            inline: $inline,
        );
    }

    /** @return array<string,mixed> */
    public function invoiceData(Order $order): array
    {
        $order->loadMissing(['items', 'payments', 'coupon']);

        $payment = $order->payments->firstWhere('status', \App\Enums\PaymentStatus::Succeeded);

        return [
            'documentTitle' => 'Invoice',
            'documentType' => 'Invoice',
            'documentReference' => $this->invoiceNumber($order),
            'documentStatus' => $order->status->label(),
            'invoiceNumber' => $this->invoiceNumber($order),
            'order' => $order,
            'recipient' => $this->factory->recipient($order),
            'lines' => $this->factory->lines($order),
            'totals' => $this->factory->totals($order),
            'issueDate' => $order->created_at ?? CarbonImmutable::now(),
            'dueDate' => $this->dueDate($order),
            'paidAt' => $payment?->paid_at ?? $order->paid_at,
        ];
    }

    /* ------------------------------------------------------------------ *
     * Receipt
     * ------------------------------------------------------------------ */

    public function receipt(Order $order, bool $inline = true): Response
    {
        return $this->renderer->response(
            view: 'pdf.receipt',
            filename: 'receipt-'.$order->number.'.pdf',
            data: $this->receiptData($order),
            inline: $inline,
            // A5: a receipt is kept on a phone or folded into a wallet.
            paper: 'a5',
        );
    }

    /** @return array<string,mixed> */
    public function receiptData(Order $order): array
    {
        $order->loadMissing(['items', 'payments']);

        return [
            'documentTitle' => 'Receipt',
            'documentType' => 'Receipt',
            'documentReference' => $this->receiptNumber($order),
            'documentStatus' => $order->paid_at ? 'Paid '.$order->paid_at->format('j M Y') : null,
            'receiptNumber' => $this->receiptNumber($order),
            'order' => $order,
            'payment' => $order->payments->firstWhere('status', \App\Enums\PaymentStatus::Succeeded)
                ?? $order->payments->first(),
            'recipient' => $this->factory->recipient($order),
            'lines' => $this->factory->lines($order),
            'totals' => $this->factory->totals($order),
            'issueDate' => $order->paid_at ?? $order->created_at ?? CarbonImmutable::now(),
        ];
    }

    /* ------------------------------------------------------------------ *
     * Packing slip
     * ------------------------------------------------------------------ */

    public function packingSlip(Order $order, bool $inline = true): Response
    {
        return $this->renderer->response(
            view: 'pdf.packing-slip',
            filename: 'packing-slip-'.$order->number.'.pdf',
            data: $this->packingSlipData($order),
            inline: $inline,
        );
    }

    /** @return array<string,mixed> */
    public function packingSlipData(Order $order): array
    {
        $order->loadMissing(['items', 'vendorOrders.vendor', 'vendorOrders.items']);

        return [
            'documentTitle' => 'Packing slip',
            'documentType' => 'Packing slip',
            'documentReference' => $order->number,
            'documentStatus' => $order->status->label(),
            'order' => $order,
            'issueDate' => CarbonImmutable::now(),
        ];
    }

    /* ------------------------------------------------------------------ *
     * Credit note
     * ------------------------------------------------------------------ */

    public function creditNote(Order $order, ?string $reason = null, bool $inline = true): Response
    {
        return $this->renderer->response(
            view: 'pdf.credit-note',
            filename: 'credit-note-'.$order->number.'.pdf',
            data: $this->creditNoteData($order, $reason),
            inline: $inline,
        );
    }

    /** @return array<string,mixed> */
    public function creditNoteData(Order $order, ?string $reason = null): array
    {
        // `items.order` is loaded because each line formats its own currency via
        // $item->order; without it this is a query per line.
        $order->loadMissing(['items.order', 'payments']);

        $refunded = (int) $order->payments->sum('refunded_minor');

        return [
            'documentTitle' => 'Credit note',
            'documentType' => 'Credit note',
            'documentReference' => $this->creditNoteNumber($order),
            'documentStatus' => $refunded > 0 ? 'Credit issued' : 'Awaiting refund',
            'creditNoteNumber' => $this->creditNoteNumber($order),
            'invoiceNumber' => $this->invoiceNumber($order),
            'creditMinor' => $refunded,
            'reason' => $reason,
            'order' => $order,
            'recipient' => $this->factory->recipient($order),
            'totals' => $this->factory->totals($order, paidMinor: 0),
            'issueDate' => CarbonImmutable::now(),
        ];
    }

    /* ------------------------------------------------------------------ *
     * Vendor statement
     * ------------------------------------------------------------------ */

    public function vendorStatement(Order $order, VendorOrder $vendorOrder, bool $inline = true): Response
    {
        $order->loadMissing(['items']);
        $vendorOrder->loadMissing(['items', 'vendor']);

        return $this->renderer->response(
            view: 'pdf.vendor-statement',
            filename: 'statement-'.$vendorOrder->number.'.pdf',
            data: [
                'documentTitle' => 'Vendor statement',
                'documentType' => 'Statement',
                'documentReference' => $this->statementNumber($vendorOrder),
                'documentStatus' => $vendorOrder->status->label(),
                'statementNumber' => $this->statementNumber($vendorOrder),
                'vendorOrder' => $vendorOrder,
                'order' => $order,
                'recipient' => new DocumentParty(
                    name: $vendorOrder->vendor?->name ?? 'Brand',
                    email: $vendorOrder->vendor?->email,
                ),
                'lines' => $this->factory->linesForVendor($order, $vendorOrder),
                'totals' => $this->factory->totalsForVendor($order, $vendorOrder),
                'issueDate' => CarbonImmutable::now(),
            ],
            inline: $inline,
        );
    }

    /* ------------------------------------------------------------------ *
     * Terms of service
     * ------------------------------------------------------------------ */

    /**
     * Terms of Service as a formal document.
     *
     * The clauses are the administrator-editable CMS page, so there is a single
     * source of truth; everything a formal document needs around them
     * (preamble, contents, jurisdiction, complaints, document control) is added
     * here because a web page does not carry it.
     */
    public function terms(bool $inline = true): Response
    {
        return $this->renderer->response(
            view: 'pdf.terms',
            filename: 'hanbellshop-terms-of-service.pdf',
            data: $this->termsData(),
            inline: $inline,
            // Published material with no personal data, so it may be cached —
            // unlike every other document in the suite.
            cacheable: true,
        );
    }

    /** @return array<string,mixed> */
    public function termsData(): array
    {
        $page = Page::query()
            ->published()
            ->where('slug', 'terms-of-service')
            ->first();

        $html = $page?->renderedContent() ?? '';
        [$sections, $sectionsHtml, $preamble] = $this->splitSections($html);

        return [
            'documentTitle' => 'Terms of Service',
            'documentType' => 'Terms of Service',
            'documentReference' => 'Version '.$this->termsVersion($page?->updated_at),
            'documentStatus' => null,
            'version' => $this->termsVersion($page?->updated_at),
            'issueDate' => $page?->updated_at ?? CarbonImmutable::now(),
            'sections' => $sections,
            'sectionsHtml' => $sectionsHtml,
            'preamble' => $preamble,
            'keyPages' => Page::query()
                ->published()
                ->whereIn('slug', [
                    'privacy-policy',
                    'returns-policy',
                    'shipping-policy',
                    'acceptable-use',
                    'cookie-policy',
                ])
                ->orderBy('position')
                ->get(),
        ];
    }

    /* ------------------------------------------------------------------ *
     * Numbering
     * ------------------------------------------------------------------ */

    public function invoiceNumber(Order $order): string
    {
        return 'INV-'.$order->number;
    }

    public function receiptNumber(Order $order): string
    {
        return 'RCP-'.$order->number;
    }

    public function creditNoteNumber(Order $order): string
    {
        return 'CRN-'.$order->number;
    }

    public function statementNumber(VendorOrder $vendorOrder): string
    {
        return 'STMT-'.$vendorOrder->number;
    }

    /**
     * A version string derived from when the terms were last edited.
     *
     * Using the content date rather than an arbitrary counter means the version
     * on a printed copy can be matched back to the revision that produced it.
     *
     * Accepts any DateTimeInterface: an Eloquent `updated_at` is an
     * Illuminate\Support\Carbon, which is NOT a CarbonImmutable, and typing the
     * parameter narrowly would reject exactly the value it is always given.
     */
    public function termsVersion(?DateTimeInterface $updatedAt): string
    {
        return 'v'.($updatedAt?->format('Y.m.d') ?? CarbonImmutable::now()->format('Y.m.d'));
    }

    /* ------------------------------------------------------------------ *
     * Internals
     * ------------------------------------------------------------------ */

    /**
     * Payment is due on receipt, but a short window is stated so a customer
     * paying by transfer has something to work to.
     */
    private function dueDate(Order $order): ?CarbonImmutable
    {
        if ($order->isPaid()) {
            return null;
        }

        $days = (int) config('hanbell.commerce.invoice_due_days', 7);

        return CarbonImmutable::parse($order->created_at ?? now())->addDays($days);
    }

    /**
     * Split the CMS terms HTML into a preamble, numbered sections, and a
     * contents list.
     *
     * The page usually opens with an unheaded paragraph before the first <h2>.
     * Left in place it dangles after the contents list with no heading, which
     * reads as a mistake; it is extracted here so the document can place it
     * under "About this document".
     *
     * Section numbers are applied here rather than trusted from the page, so the
     * printed contents always matches the body even if an editor reorders or
     * mis-numbers the headings.
     *
     * @return array{0: array<int,array{title:string,anchor:string}>, 1: string, 2: string}
     */
    private function splitSections(string $html): array
    {
        if (trim($html) === '') {
            return [[], '', '<p>Our terms are being prepared. Please contact us for the current version.</p>'];
        }

        // Anything before the first <h2> is preamble.
        $preamble = '';
        $firstHeading = stripos($html, '<h2');

        if ($firstHeading !== false && $firstHeading > 0) {
            $preamble = trim(substr($html, 0, $firstHeading));
            $html = substr($html, $firstHeading);
        }

        $sections = [];
        $counter = 0;

        $body = preg_replace_callback(
            '#<h2[^>]*>(.*?)</h2>#is',
            function (array $match) use (&$sections, &$counter): string {
                $counter++;
                $title = trim(strip_tags($match[1]));
                // Drop any numbering the editor already wrote, then apply ours.
                $clean = preg_replace('/^\d+\.\s*/', '', $title) ?? $title;

                $sections[] = ['title' => $clean, 'anchor' => 's'.$counter];

                return sprintf(
                    '<h2 id="s%d">%d. %s</h2>',
                    $counter,
                    $counter,
                    e($clean),
                );
            },
            $html,
        ) ?? $html;

        return [$sections, $body, $preamble];
    }
}
