<?php

namespace App\Services\Pdf\Data;

use App\Support\Money;

/**
 * A totals block.
 *
 * Every figure is stored in minor units and formatted on demand, so the
 * document can never show a total that disagrees with the lines above it.
 */
final readonly class DocumentTotals
{
    public function __construct(
        public int $subtotalMinor,
        public int $shippingMinor = 0,
        public int $discountMinor = 0,
        public int $taxMinor = 0,
        public int $totalMinor = 0,
        public string $currency = 'NGN',
        public ?int $paidMinor = null,
        public ?int $balanceMinor = null,
        public ?string $taxLabel = 'VAT',
        public ?float $taxRate = null,
        /** Broker commission withheld from the vendor, on a vendor statement. */
        public ?int $commissionMinor = null,
        /** What the vendor is actually owed. */
        public ?int $payoutMinor = null,
    ) {}

    public function format(int $minor): string
    {
        return Money::format($minor, $this->currency);
    }

    public function formattedSubtotal(): string
    {
        return $this->format($this->subtotalMinor);
    }

    public function formattedShipping(): string
    {
        return $this->shippingMinor === 0 ? 'Free' : $this->format($this->shippingMinor);
    }

    public function formattedDiscount(): ?string
    {
        return $this->discountMinor === 0 ? null : $this->format($this->discountMinor);
    }

    public function formattedTax(): ?string
    {
        return $this->taxMinor === 0 ? null : $this->format($this->taxMinor);
    }

    public function formattedTotal(): string
    {
        return $this->format($this->totalMinor);
    }

    public function formattedPaid(): ?string
    {
        return $this->paidMinor === null ? null : $this->format($this->paidMinor);
    }

    public function formattedBalance(): ?string
    {
        return $this->balanceMinor === null ? null : $this->format($this->balanceMinor);
    }

    public function formattedCommission(): ?string
    {
        return $this->commissionMinor === null ? null : $this->format($this->commissionMinor);
    }

    public function formattedPayout(): ?string
    {
        return $this->payoutMinor === null ? null : $this->format($this->payoutMinor);
    }

    /** Tax line label, e.g. "VAT (7.5%)". */
    public function taxLabel(): string
    {
        $label = $this->taxLabel ?: 'Tax';

        return $this->taxRate === null
            ? $label
            : sprintf('%s (%s%%)', $label, rtrim(rtrim(number_format($this->taxRate, 2), '0'), '.'));
    }

    public function hasBalance(): bool
    {
        return $this->balanceMinor !== null && $this->balanceMinor > 0;
    }

    public function isFullyPaid(): bool
    {
        return $this->balanceMinor !== null && $this->balanceMinor <= 0;
    }
}
