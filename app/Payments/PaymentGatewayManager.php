<?php

namespace App\Payments;

use App\Enums\PaymentProvider;
use App\Payments\Contracts\PaymentGateway;
use App\Payments\Exceptions\PaymentFailedException;
use App\Payments\Gateways\BankTransferGateway;
use App\Payments\Gateways\CashOnDeliveryGateway;
use App\Payments\Gateways\FlutterwaveGateway;
use App\Payments\Gateways\PaystackGateway;
use App\Payments\Gateways\StripeGateway;
use Illuminate\Support\Collection;

/**
 * Resolves payment rails and reports which are genuinely usable.
 *
 * The checkout page asks `availableFor($currency)` rather than listing every
 * provider, so an unconfigured rail is never offered — and never faked.
 */
class PaymentGatewayManager
{
    /** @var array<string, PaymentGateway> */
    private array $resolved = [];

    public function __construct(private readonly array $gateways = []) {}

    /**
     * The default gateway list, in the order they should be presented.
     *
     * @return array<int,class-string<PaymentGateway>>
     */
    public static function defaultGateways(): array
    {
        return [
            PaystackGateway::class,
            FlutterwaveGateway::class,
            StripeGateway::class,
            BankTransferGateway::class,
            CashOnDeliveryGateway::class,
        ];
    }

    public function gateway(PaymentProvider|string $provider): PaymentGateway
    {
        $key = $provider instanceof PaymentProvider ? $provider->value : $provider;

        if (isset($this->resolved[$key])) {
            return $this->resolved[$key];
        }

        foreach ($this->gateways() as $gateway) {
            if ($gateway->provider()->value === $key) {
                return $this->resolved[$key] = $gateway;
            }
        }

        throw new PaymentFailedException("No payment gateway is registered for \"{$key}\".");
    }

    /** @return Collection<int,PaymentGateway> */
    public function gateways(): Collection
    {
        $classes = $this->gateways ?: static::defaultGateways();

        return collect($classes)->map(function ($gateway) {
            return $gateway instanceof PaymentGateway ? $gateway : app($gateway);
        });
    }

    /**
     * Rails that are configured *and* able to settle this currency. This is the
     * only list the checkout UI should ever show.
     *
     * @return Collection<int,PaymentGateway>
     */
    public function availableFor(string $currency): Collection
    {
        return $this->gateways()
            ->filter(fn (PaymentGateway $g) => $g->isConfigured() && $g->supports($currency))
            ->values();
    }

    /**
     * Provider value => label, for select inputs.
     *
     * @return array<string,string>
     */
    public function optionsFor(string $currency): array
    {
        return $this->availableFor($currency)
            ->mapWithKeys(fn (PaymentGateway $g) => [$g->provider()->value => $g->label()])
            ->all();
    }

    public function isAvailable(PaymentProvider|string $provider, string $currency): bool
    {
        try {
            $gateway = $this->gateway($provider);
        } catch (PaymentFailedException) {
            return false;
        }

        return $gateway->isConfigured() && $gateway->supports($currency);
    }

    /** True when no online rail is configured at all — the checkout warns instead. */
    public function hasAnyOnlineGateway(string $currency): bool
    {
        return $this->availableFor($currency)->contains(fn (PaymentGateway $g) => $g->provider()->isOnline());
    }
}
