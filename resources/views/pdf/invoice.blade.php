@php
    /*
     * Invoice.
     *
     * Every figure comes from the order's stored columns (the snapshot taken at
     * checkout), never from the live product — a later price change must not
     * alter an issued invoice.
     *
     * The vendor column is shown only when the order spans more than one brand,
     * because on a single-brand order it is redundant noise.
     */
    $slot = view('pdf.partials.invoice-body', get_defined_vars())->render();
@endphp

{{-- Medium density: the invoice carries enough content that the default rhythm spilled onto a second page. --}}
@include('pdf.layouts.base', ['slot' => $slot, 'medium' => true])
