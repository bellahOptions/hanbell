{{--
    The invoice / document body: the line-item table.

    The maker is rendered as part of each line's meta text rather than as its own
    column. The vendor names in the catalogue are long ("Ndị Aba Shoemakers",
    "Northern Loom"), so a dedicated column both wrapped the heading onto two
    lines and squeezed the description; inline, the brand name sits directly
    under the product it belongs to, which is clearer on a multi-brand order.
--}}
<table class="items">
    <thead>
        <tr>
            <th style="width: 52%;">Description</th>
            <th class="num" style="width: 10%;">Qty</th>
            <th class="num" style="width: 18%;">Unit price</th>
            <th class="num" style="width: 20%;">Amount</th>
        </tr>
    </thead>

    <tbody>
        @foreach ($lines as $line)
            <tr>
                <td>
                    <div class="item-name">{{ $line->description }}</div>

                    @if ($line->vendor)
                        <div class="item-vendor">{{ $line->vendor }}</div>
                    @endif

                    @php
                        $meta = implode(' · ', array_filter([
                            $line->variant,
                            $line->sku ? 'SKU '.$line->sku : null,
                        ]));
                    @endphp

                    @if ($meta !== '')
                        <div class="item-meta">{{ $meta }}</div>
                    @endif

                    @if ($line->note)
                        <div class="item-meta">{{ $line->note }}</div>
                    @endif
                </td>

                <td class="num">{{ $line->quantity }}</td>
                <td class="num">{{ $line->formattedUnitPrice() }}</td>
                <td class="num">{{ $line->formattedLineTotal() }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
