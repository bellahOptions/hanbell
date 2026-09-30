<?php

namespace App\Services\Pdf;

use App\Models\Order;
use App\Models\VendorOrder;
use App\Services\Pdf\Data\DocumentLine;
use App\Services\Pdf\Data\DocumentParty;
use App\Services\Pdf\Data\DocumentTotals;

/**
 * Builds the data a financial document needs from an Order.
 *
 * All figures come from the order's own stored columns — never recalculated
 * from the live product — because the order row is the snapshot of what the
 * customer was actually charged. Recomputing here would let a later price edit
 * silently rewrite an issued invoice.
 */
class DocumentFactory
{
    public function __construct(private readonly PdfRenderer $renderer) {}

    /**
     * The issuing party: the legal entity, trading as the brand.
     */
    public function issuer(): DocumentParty
    {
        $operator = $this->renderer->operator();

        return new DocumentParty(
            name: $operator['name'],
            addressLines: $operator['address_lines'],
            email: $operator['email'],
            phone: $operator['phone'],
            registrationNumber: $operator['registration_number'],
            tin: $operator['tin'],
            vatNumber: $operator['vat_number'],
            label: 'From',
        );
    }

    /**
     * The customer being billed.
     */
    public function recipient(Order $order): DocumentParty
    {
        return new DocumentParty(
            name: $order->customer_name,
            addressLines: $order->shippingAddressLines(),
            email: $order->email,
            phone: $order->phone,
            label: 'Bill to',
        );
    }

    /**
     * Lines for a whole order.
     *
     * @return array<int,DocumentLine>
     */
    public function lines(Order $order): array
    {
        return $order->items
            ->map(fn ($item) => new DocumentLine(
                description: $item->product_name,
                quantity: (int) $item->quantity,
                unitPriceMinor: (int) $item->unit_price_minor,
                lineTotalMinor: (int) $item->line_total_minor,
                currency: $order->currency,
                sku: $item->sku,
                variant: $item->variant_label,
                vendor: $item->vendor_name,
            ))
            ->values()
            ->all();
    }

    /**
     * Lines for one vendor's slice of an order, for a vendor-facing statement.
     *
     * @return array<int,DocumentLine>
     */
    public function linesForVendor(Order $order, VendorOrder $vendorOrder): array
    {
        return $vendorOrder->items
            ->map(fn ($item) => new DocumentLine(
                description: $item->product_name,
                quantity: (int) $item->quantity,
                unitPriceMinor: (int) $item->unit_price_minor,
                lineTotalMinor: (int) $item->line_total_minor,
                currency: $order->currency,
                sku: $item->sku,
                variant: $item->variant_label,
                vendor: null,
            ))
            ->values()
            ->all();
    }

    /**
     * Totals for a whole order.
     */
    public function totals(Order $order, ?int $paidMinor = null): DocumentTotals
    {
        $paid = $paidMinor ?? ($order->isPaid() ? (int) $order->total_minor : 0);
        $balance = max(0, (int) $order->total_minor - $paid);

        return new DocumentTotals(
            subtotalMinor: (int) $order->subtotal_minor,
            shippingMinor: (int) $order->shipping_minor,
            discountMinor: (int) $order->discount_minor,
            taxMinor: (int) $order->tax_minor,
            totalMinor: (int) $order->total_minor,
            currency: $order->currency,
            paidMinor: $paid,
            balanceMinor: $balance,
            taxLabel: 'VAT',
            taxRate: (float) config('hanbell.tax.vat_percent', 7.5),
        );
    }

    /**
     * Totals for a vendor statement: commission withheld and what is payable.
     */
    public function totalsForVendor(Order $order, VendorOrder $vendorOrder): DocumentTotals
    {
        return new DocumentTotals(
            subtotalMinor: (int) $vendorOrder->subtotal_minor,
            shippingMinor: (int) $vendorOrder->shipping_minor,
            totalMinor: (int) $vendorOrder->subtotal_minor,
            currency: $order->currency,
            commissionMinor: (int) $vendorOrder->commission_minor,
            payoutMinor: (int) $vendorOrder->payout_minor,
            taxLabel: null,
            taxRate: null,
        );
    }
}
