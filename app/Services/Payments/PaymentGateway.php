<?php

namespace App\Services\Payments;

use App\Models\Payment;
use Illuminate\Http\Request;

/**
 * What AngkorRide needs from a payment provider. The test gateway and, later,
 * ABA PayWay both fit this, so the rest of the app never changes.
 */
interface PaymentGateway
{
    /** Short name stored on each payment, e.g. "test" or "payway". */
    public function name(): string;

    /** Where to send the customer to pay for this payment. */
    public function checkoutUrl(Payment $payment): string;

    /**
     * Check a provider's callback (signature etc.) and say which payment it is about.
     * Returns ['tran_id' => string] or null if the callback cannot be trusted.
     * Never use the callback's own "paid" claim: ask lookup() instead.
     */
    public function parseWebhook(Request $request): ?array;

    /**
     * Ask the provider directly what happened to this payment.
     * Returns ['outcome' => 'paid'|'failed', 'amount' => float, 'ref' => ?string, 'method' => ?string],
     * or null while the payment is still undecided.
     */
    public function lookup(Payment $payment): ?array;
}