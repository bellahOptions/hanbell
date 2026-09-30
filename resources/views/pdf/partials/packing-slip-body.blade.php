@php
    $vendors = $order->vendorOrders->load('vendor', 'items');
@endphp

<table class="panels">
    <tr>
        <td>
            <p class="panel-label">Deliver to</p>
            <p class="party-name">{{ $order->shipping_recipient_name }}</p>

            @foreach ($order->shippingAddressLines() as $line)
                <p class="party-line">{{ $line }}</p>
            @endforeach

            @if ($order->shipping_phone)
                <p class="party-meta">{{ $order->shipping_phone }}</p>
            @endif
        </td>
        <td>
            <table class="facts">
                <tr>
                    <td class="k">Order reference</td>
                    <td class="v">{{ $order->number }}</td>
                </tr>
                <tr>
                    <td class="k">Packed</td>
                    <td class="v">{{ $issueDate->format('j M Y') }}</td>
                </tr>
                <tr>
                    <td class="k">Parcels</td>
                    <td class="v">{{ $vendors->count() }}</td>
                </tr>
                <tr>
                    <td class="k">Total items</td>
                    <td class="v">{{ $order->items->sum('quantity') }}</td>
                </tr>
            </table>
        </td>
    </tr>
</table>

@if ($order->notes)
    <div class="block avoid-break" style="margin-top: 0;">
        <h3>Customer note</h3>
        <p class="small" style="margin: 0;">{{ $order->notes }}</p>
    </div>
@endif

@foreach ($vendors as $index => $vendorOrder)
    <div class="{{ $index > 0 ? 'page-break' : '' }} avoid-break">
        <table class="panels" style="margin-bottom: 3mm;">
            <tr>
                <td style="width: 60%;">
                    <p class="panel-label">Parcel {{ $index + 1 }} of {{ $vendors->count() }}</p>
                    <p class="party-name">{{ $vendorOrder->vendor?->name ?? 'HanbellShop' }}</p>
                    <p class="party-meta">{{ $vendorOrder->number }}</p>
                </td>
                <td style="width: 40%;">
                    <table class="facts">
                        <tr>
                            <td class="k">Items</td>
                            <td class="v">{{ $vendorOrder->items->sum('quantity') }}</td>
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
                    </table>
                </td>
            </tr>
        </table>

        <table class="items">
            <thead>
                <tr>
                    <th style="width: 8%;">&#10003;</th>
                    <th style="width: 54%;">Item</th>
                    <th style="width: 20%;">SKU</th>
                    <th class="num" style="width: 18%;">Qty</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($vendorOrder->items as $item)
                    <tr>
                        <td class="center">&#9744;</td>
                        <td>
                            <div class="item-name">{{ $item->product_name }}</div>
                            @if ($item->variant_label)
                                <div class="item-meta">{{ $item->variant_label }}</div>
                            @endif
                        </td>
                        <td class="small">{{ $item->sku ?: '—' }}</td>
                        <td class="num">{{ $item->quantity }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endforeach

<div class="block avoid-break">
    <h3>Packing notes</h3>
    <p class="small" style="margin: 0;">
        This slip intentionally shows no prices. If anything is missing or damaged,
        contact us quoting order {{ $order->number }} within 48 hours of delivery.
        Returns are governed by our Returns Policy.
    </p>
</div>
