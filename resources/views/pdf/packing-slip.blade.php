@php
    /*
     * Packing slip.
     *
     * Deliberately carries NO prices. It travels inside the parcel and is often
     * handed to a courier or left with a neighbour, so printing what the
     * customer paid would leak commercial information to whoever opens it.
     * Quantities and variants are what the warehouse actually needs.
     */
    $slot = view('pdf.partials.packing-slip-body', get_defined_vars())->render();
@endphp

@include('pdf.layouts.base', ['slot' => $slot])
