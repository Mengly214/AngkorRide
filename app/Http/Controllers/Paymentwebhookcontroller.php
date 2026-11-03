<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Services\Payments\PaymentGateways;
use App\Services\Payments\PaymentService;
use Illuminate\Http\Request;

/**
 * Payment providers call this from their servers when a payment changes.
 * We only use the call to learn WHICH payment to check; the result itself
 * comes from asking the provider (PaymentService::verify).
 */
class PaymentWebhookController extends Controller
{
    public function handle(Request $request, string $driver, PaymentService $payments)
    {
        $gateway = PaymentGateways::named($driver);
        abort_if($gateway === null, 404);

        $parsed = $gateway->parseWebhook($request);
        if ($parsed === null || empty($parsed['tran_id'])) {
            return response()->json(['ok' => false], 401); // bad or missing signature
        }

        $payment = Payment::query()->where('tran_id', $parsed['tran_id'])->where('provider', $gateway->name())->first();
        if ($payment) {
            $payments->verify($payment);
        }

        // Always answer 200 for a trusted call so the provider stops retrying.
        return response()->json(['ok' => true]);
    }
}