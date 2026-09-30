<?php

namespace App\Services\Inventory;

use App\Enums\InventoryMovementReason;
use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class InsufficientStockException extends RuntimeException
{
    public function __construct(
        public readonly Product $product,
        public readonly ?ProductVariant $variant,
        public readonly int $requested,
        public readonly int $available,
    ) {
        parent::__construct(sprintf(
            'Only %d left of "%s"%s, but %d were requested.',
            $available,
            $product->name,
            $variant ? ' ('.$variant->label().')' : '',
            $requested,
        ));
    }
}

/**
 * Every stock mutation in the application goes through this service.
 *
 * Two rules make overselling impossible:
 *   1. Each mutation runs in a transaction and locks the inventory row, so two
 *      concurrent checkouts cannot both read the same "3 available".
 *   2. Checkout *reserves* stock first and only converts the reservation into a
 *      real deduction once payment is verified. A failed payment releases the
 *      reservation rather than leaking it.
 *
 * Every change writes an inventory_movements audit row, so any stock figure can
 * be explained after the fact.
 */
class InventoryService
{
    /**
     * Find (or lazily create) the inventory row for a product/variant pair.
     */
    public function for(Product $product, ?ProductVariant $variant = null): Inventory
    {
        return Inventory::firstOrCreate(
            [
                'product_id' => $product->id,
                'variant_id' => $variant?->id,
            ],
            [
                'quantity_on_hand' => 0,
                'quantity_reserved' => 0,
                'low_stock_threshold' => 3,
            ],
        );
    }

    /**
     * Add stock (a delivery from the vendor, or a return).
     */
    public function restock(
        Product $product,
        ?ProductVariant $variant,
        int $quantity,
        ?Model $reference = null,
        ?int $userId = null,
        ?string $note = null,
        InventoryMovementReason $reason = InventoryMovementReason::Restock,
    ): Inventory {
        if ($quantity <= 0) {
            throw new RuntimeException('Restock quantity must be greater than zero.');
        }

        return $this->mutate($product, $variant, $quantity, $reason, $reference, $userId, $note);
    }

    /**
     * Set stock to an absolute figure, recording the delta.
     * Used by the admin stock editor and stock-take corrections.
     */
    public function adjustTo(
        Product $product,
        ?ProductVariant $variant,
        int $newOnHand,
        ?int $userId = null,
        ?string $note = null,
    ): Inventory {
        if ($newOnHand < 0) {
            throw new RuntimeException('Stock cannot be negative.');
        }

        return DB::transaction(function () use ($product, $variant, $newOnHand, $userId, $note) {
            $inventory = $this->lock($product, $variant);
            $delta = $newOnHand - $inventory->quantity_on_hand;

            if ($delta === 0) {
                return $inventory;
            }

            $inventory->quantity_on_hand = $newOnHand;
            $inventory->save();

            $this->log($inventory, $delta, InventoryMovementReason::Adjustment, null, $userId, $note);

            return $inventory->refresh();
        });
    }

    /**
     * Reserve stock for a pending order.
     *
     * Reserving rather than deducting is what lets an unpaid order expire
     * cleanly: the stock returns to saleable without an offsetting entry.
     */
    public function reserve(
        Product $product,
        ?ProductVariant $variant,
        int $quantity,
        ?Model $reference = null,
    ): Inventory {
        if ($quantity <= 0) {
            throw new RuntimeException('Reservation quantity must be greater than zero.');
        }

        return DB::transaction(function () use ($product, $variant, $quantity, $reference) {
            $inventory = $this->lock($product, $variant);

            $available = max(0, $inventory->quantity_on_hand - $inventory->quantity_reserved);

            if ($available < $quantity && ! $inventory->allow_backorder) {
                throw new InsufficientStockException($product, $variant, $quantity, $available);
            }

            $inventory->quantity_reserved += $quantity;
            $inventory->save();

            $this->log($inventory, 0, InventoryMovementReason::Reservation, $reference, null, sprintf(
                'Reserved %d (available now %d)',
                $quantity,
                max(0, $inventory->quantity_on_hand - $inventory->quantity_reserved),
            ));

            return $inventory->refresh();
        });
    }

    /**
     * Release a reservation without deducting stock — a cancelled or expired
     * order, or a payment that failed.
     */
    public function release(
        Product $product,
        ?ProductVariant $variant,
        int $quantity,
        ?Model $reference = null,
        ?string $note = null,
    ): Inventory {
        if ($quantity <= 0) {
            return $this->for($product, $variant);
        }

        return DB::transaction(function () use ($product, $variant, $quantity, $reference, $note) {
            $inventory = $this->lock($product, $variant);

            // Never release more than is actually held: a double-release would
            // otherwise inflate availability out of thin air.
            $release = min($quantity, $inventory->quantity_reserved);
            $inventory->quantity_reserved -= $release;
            $inventory->save();

            $this->log(
                $inventory,
                0,
                InventoryMovementReason::ReservationRelease,
                $reference,
                null,
                $note ?? sprintf('Released %d reservation(s)', $release),
            );

            return $inventory->refresh();
        });
    }

    /**
     * Convert a reservation into a real deduction. Called once payment is
     * verified. Without a prior reservation (e.g. pay-on-delivery) this
     * deducts directly, subject to availability.
     */
    public function commitSale(
        Product $product,
        ?ProductVariant $variant,
        int $quantity,
        ?Model $reference = null,
    ): Inventory {
        if ($quantity <= 0) {
            throw new RuntimeException('Sale quantity must be greater than zero.');
        }

        return DB::transaction(function () use ($product, $variant, $quantity, $reference) {
            $inventory = $this->lock($product, $variant);

            $reserved = min($quantity, $inventory->quantity_reserved);
            $unreserved = $quantity - $reserved;

            // If part of the sale was never reserved, it must still be in stock.
            $available = max(0, $inventory->quantity_on_hand - $inventory->quantity_reserved);

            if ($unreserved > 0 && $available < $unreserved && ! $inventory->allow_backorder) {
                throw new InsufficientStockException($product, $variant, $unreserved, $available);
            }

            $inventory->quantity_reserved -= $reserved;
            $inventory->quantity_on_hand -= $quantity;
            $inventory->save();

            $this->log($inventory, -$quantity, InventoryMovementReason::Sale, $reference, null, sprintf(
                'Sold %d (%d from reservation, %d direct)',
                $quantity,
                $reserved,
                $unreserved,
            ));

            $product->increment('sales_count', $quantity);

            return $inventory->refresh();
        });
    }

    /**
     * Write off damaged or lost stock.
     */
    public function writeOff(
        Product $product,
        ?ProductVariant $variant,
        int $quantity,
        ?int $userId = null,
        ?string $note = null,
    ): Inventory {
        if ($quantity <= 0) {
            throw new RuntimeException('Write-off quantity must be greater than zero.');
        }

        return DB::transaction(function () use ($product, $variant, $quantity, $userId, $note) {
            $inventory = $this->lock($product, $variant);

            $inventory->quantity_on_hand = max(0, $inventory->quantity_on_hand - $quantity);
            $inventory->save();

            $this->log($inventory, -$quantity, InventoryMovementReason::Damage, null, $userId, $note);

            return $inventory->refresh();
        });
    }

    /* ------------------------------------------------------------------ *
     * Internals
     * ------------------------------------------------------------------ */

    private function mutate(
        Product $product,
        ?ProductVariant $variant,
        int $delta,
        InventoryMovementReason $reason,
        ?Model $reference,
        ?int $userId,
        ?string $note,
    ): Inventory {
        return DB::transaction(function () use ($product, $variant, $delta, $reason, $reference, $userId, $note) {
            $inventory = $this->lock($product, $variant);

            $inventory->quantity_on_hand = max(0, $inventory->quantity_on_hand + $delta);
            $inventory->save();

            $this->log($inventory, $delta, $reason, $reference, $userId, $note);

            return $inventory->refresh();
        });
    }

    /**
     * Lock the inventory row for update, creating it first if needed.
     *
     * `lockForUpdate` is a no-op on SQLite (the test database) and a real row
     * lock on MySQL, which is exactly the behaviour wanted in each place.
     */
    private function lock(Product $product, ?ProductVariant $variant): Inventory
    {
        $this->for($product, $variant);

        return Inventory::query()
            ->where('product_id', $product->id)
            ->where('variant_id', $variant?->id)
            ->lockForUpdate()
            ->firstOrFail();
    }

    private function log(
        Inventory $inventory,
        int $quantityChange,
        InventoryMovementReason $reason,
        ?Model $reference,
        ?int $userId,
        ?string $note,
    ): void {
        InventoryMovement::create([
            'inventory_id' => $inventory->id,
            'product_id' => $inventory->product_id,
            'variant_id' => $inventory->variant_id,
            'reason' => $reason,
            'quantity_change' => $quantityChange,
            'quantity_after' => $inventory->quantity_on_hand,
            'reference_type' => $reference?->getMorphClass(),
            'reference_id' => $reference?->getKey(),
            'user_id' => $userId,
            'note' => $note,
        ]);
    }
}
