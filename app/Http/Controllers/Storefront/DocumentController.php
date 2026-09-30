<?php

namespace App\Http\Controllers\Storefront;

use App\Models\Order;
use App\Models\VendorOrder;
use App\Services\Pdf\DocumentService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * PDF document downloads.
 *
 * Authorisation is the whole job of this controller, and it is deliberately
 * strict: an invoice carries a customer's name, address and purchase history,
 * so the opaque order token alone is treated as sufficient only for guest
 * orders. A signed-in order must also match the signed-in user.
 *
 * A missing or unauthorised document is a 404 rather than a 403 — a 403 would
 * confirm that the order exists.
 */
class DocumentController
{
    public function __construct(private readonly DocumentService $documents) {}

    /**
     * Invoice. Only available once paid, because an invoice is a demand for
     * payment and issuing one for an unpaid order invites a payment we would
     * then have to refund.
     */
    public function invoice(Request $request, string $order): Response
    {
        $model = $this->authorisedOrder($order);

        abort_unless($model->isPaid(), 404);

        return $this->documents->invoice($model, inline: $this->inline($request));
    }

    /** Receipt, also only meaningful once paid. */
    public function receipt(Request $request, string $order): Response
    {
        $model = $this->authorisedOrder($order);

        abort_unless($model->isPaid(), 404);

        return $this->documents->receipt($model, inline: $this->inline($request));
    }

    /** Credit note, only once something has actually been refunded. */
    public function creditNote(Request $request, string $order): Response
    {
        $model = $this->authorisedOrder($order);

        $refunded = (int) $model->payments()->sum('refunded_minor');

        abort_unless($refunded > 0 || $model->status === \App\Enums\OrderStatus::Refunded, 404);

        return $this->documents->creditNote($model, inline: $this->inline($request));
    }

    /**
     * Packing slip. Available as soon as the order exists, because it is what
     * travels in the parcel.
     */
    public function packingSlip(Request $request, string $order): Response
    {
        $model = $this->authorisedOrder($order);

        return $this->documents->packingSlip($model, inline: $this->inline($request));
    }

    /**
     * Vendor statement.
     *
     * A vendor may only pull a statement for their own slice of an order; an
     * administrator may pull any. The vendor order is resolved from the order's
     * own sub-orders rather than from a client-supplied id, so an id from the
     * URL cannot reach another brand's figures.
     */
    public function vendorStatement(Request $request, string $order, int $vendorOrder): Response
    {
        $model = (new Order)->resolveRouteBinding($order);

        abort_if($model === null, 404);

        $vendorOrderModel = VendorOrder::where('order_id', $model->id)->find($vendorOrder);

        abort_if($vendorOrderModel === null, 404);

        $user = $request->user();

        abort_unless(
            $user !== null && ($user->isAdmin() || $user->vendor?->id === $vendorOrderModel->vendor_id),
            404,
        );

        return $this->documents->vendorStatement($model, $vendorOrderModel, inline: $this->inline($request));
    }

    /**
     * Terms of Service as a PDF. Public — the terms are published material and
     * a customer must be able to read them before deciding whether to buy.
     */
    public function terms(Request $request): Response
    {
        return $this->documents->terms(inline: $this->inline($request));
    }

    /* ------------------------------------------------------------------ */

    /**
     * Resolve an order via its signed token, then check ownership.
     */
    private function authorisedOrder(string $token): Order
    {
        $order = (new Order)->resolveRouteBinding($token);

        abort_if($order === null, 404);

        // A guest order (no user_id) is protected by the opaque, expiring token.
        if ($order->user_id !== null && $order->user_id !== auth()->id()) {
            abort(404);
        }

        return $order;
    }

    /** `?download=1` forces a file download; otherwise the PDF opens in a tab. */
    private function inline(Request $request): bool
    {
        return ! $request->boolean('download');
    }
}
