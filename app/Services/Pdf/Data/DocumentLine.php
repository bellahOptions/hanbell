<?php

namespace App\Services\Pdf\Data;

use App\Support\Money;

/**
 * One line on a financial document.
 *
 * Amounts are integer minor units and the formatted strings are derived from
 * them, so a template can never disagree with the arithmetic.
 */
final readonly class DocumentLine
{
    public function __construct(
        public string $description,
        public int $quantity,
        public int $unitPriceMinor,
        public int $lineTotalMinor,
        public string $currency = 'NGN',
        public ?string $sku = null,
        public ?string $variant = null,
        public ?string $vendor = null,
        public ?string $note = null,
        public ?int $discountMinor = null,
        public ?int $taxMinor = null,
    ) {}

    public function formattedUnitPrice(): string
    {
        return Money::format($this->unitPriceMinor, $this->currency);
    }

    public function formattedLineTotal(): string
    {
        return Money::format($this->lineTotalMinor, $this->currency);
    }

    public function formattedDiscount(): ?string
    {
        return $this->discountMinor === null
            ? null
            : Money::format($this->discountMinor, $this->currency);
    }

    public function formattedTax(): ?string
    {
        return $this->taxMinor === null
            ? null
            : Money::format($this->taxMinor, $this->currency);
    }

    /** Secondary line under the description: SKU, variant, maker. */
    public function metaLine(): string
    {
        return implode(' · ', array_filter([
            $this->variant,
            $this->sku ? 'SKU '.$this->sku : null,
            $this->vendor,
        ]));
    }
}
