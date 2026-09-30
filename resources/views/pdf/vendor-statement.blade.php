@php
    /*
     * Vendor statement.
     *
     * What a brand is owed for one order: their own lines, the commission
     * withheld, and the net payable. Scoped to a single VendorOrder so a vendor
     * never sees another brand's takings or the customer's whole basket.
     */
    $slot = view('pdf.partials.vendor-statement-body', get_defined_vars())->render();
@endphp

@include('pdf.layouts.base', ['slot' => $slot])
