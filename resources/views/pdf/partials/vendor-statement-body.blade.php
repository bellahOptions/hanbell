<table class="panels">
    <tr>
        <td>
            <p class="panel-label">Statement for</p>
            <p class="party-name">{{ $vendorOrder->vendor?->name ?? $recipient->name }}</p>

            @if ($vendorOrder->vendor)
                @foreach (array_filter([$vendorOrder->vendor->city, $vendorOrder->vendor->state]) as $line)
                    <p class="party-line">{{ $line }}</p>
                @endforeach

                @if ($vendorOrder->vendor->email)
                    <p class="party-meta">{{ $vendorOrder->vendor->email }}</p>
                @endif
            @endif
        </td>
        <td>
            <table class="facts">
                <tr>
                    <td class="k">Statement number</td>
                    <td class="v">{{ $statementNumber }}</td>
                </tr>
                <tr>
                    <td class="k">Vendor order</td>
                    <td class="v">{{ $vendorOrder->number }}</td>
                </tr>
                <tr>
                    <td class="k">Customer order</td>
                    <td class="v">{{ $order->number }}</td>
                </tr>
                <tr>
                    <td class="k">Date issued</td>
                    <td class="v">{{ $issueDate->format('j M Y') }}</td>
                </tr>
                <tr>
                    <td class="k">Currency</td>
                    <td class="v">{{ $order->currency }}</td>
                </tr>
            </table>
        </td>
    </tr>
</table>

@include('pdf.partials.lines', [
    'lines' => $lines,
])

{{-- The earnings summary sits above the totals rather than beside them: dompdf's
     auto table layout gave the totals cell less width than it asked for once the
     sibling cell held content, and the two overlapped. --}}
@include('pdf.partials.vendor-earnings', [
    'vendorOrder' => $vendorOrder,
    'order' => $order,
    'totals' => $totals,
])

@include('pdf.partials.totals', ['totals' => $totals])

<div class="block avoid-break">
    <h3>Commission</h3>
    <p class="small" style="margin: 0;">
        Commission is charged at
        {{ rtrim(rtrim(number_format((float) $vendorOrder->commission_percent, 2), '0'), '.') }}%
        on the discounted value of the goods in this order, as agreed when your
        brand was approved. There are no listing fees and no monthly charge.
        The net payable figure above is what will be remitted to you.
    </p>
</div>

<div class="block avoid-break">
    <h3>Fulfilment</h3>
    <table class="facts">
        <tr>
            <td class="k">Status</td>
            <td class="v">{{ $vendorOrder->status->label() }}</td>
        </tr>
        @if ($vendorOrder->courier)
            <tr>
                <td class="k">Courier</td>
                <td class="v">{{ $vendorOrder->courier }}</td>
            </tr>
        @endif
        @if ($vendorOrder->tracking_number)
            <tr>
                <td class="k">Tracking</td>
                <td class="v">{{ $vendorOrder->tracking_number }}</td>
            </tr>
        @endif
        @if ($vendorOrder->shipped_at)
            <tr>
                <td class="k">Shipped</td>
                <td class="v">{{ $vendorOrder->shipped_at->format('j M Y') }}</td>
            </tr>
        @endif
        @if ($vendorOrder->delivered_at)
            <tr>
                <td class="k">Delivered</td>
                <td class="v">{{ $vendorOrder->delivered_at->format('j M Y') }}</td>
            </tr>
        @endif
    </table>
</div>

<p class="small" style="margin-top: 4mm;">
    Ship to: {{ $order->shipping_recipient_name }}, {{ implode(', ', $order->shippingAddressLines()) }}.
    This statement is issued by {{ $operator['name'] }}@if ($operator['registration_number']), RC {{ $operator['registration_number'] }}@endif,
    the operator of {{ $brandName }}.
</p>
