<?php

namespace App\Services\Payments;

/** Picks the gateway named in config/payments.php. */
class PaymentGateways
{
    /** The gateway customers pay with right now, or null when online payment is switched off. */
    public static function current(): ?PaymentGateway
    {
        return static::named((string) config('payments.driver', 'test'));
    }

    /** A gateway by name (used by the callback URL), or null if it is not available. */
    public static function named(string $name): ?PaymentGateway
    {
        return match ($name) {
            'test' => config('payments.allow_test') ? new TestGateway() : null,
            'payway' => class_exists(\App\Services\Payments\PaywayGateway::class) ? new \App\Services\Payments\PaywayGateway() : null,
            default => null,
        };
    }
}