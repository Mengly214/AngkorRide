<?php

namespace App\Services\Payments;

use App\Models\Payment;
use Illuminate\Http\Request;

/**
 * A pretend payment page for development. No money moves. The "provider's answer"
 * is saved by the test page and read back through lookup(), the same way a real
 * provider would be asked.
 */
class TestGateway implements PaymentGateway
{
    public function name(): string
    {
        return 'test';
    }

    public function checkoutUrl(Payment $payment): string
    {
        return route('payments.test.show', $payment->id);
    }

    public function parseWebhook(Request $request): ?array
    {
        return null; // the test gateway never calls us back
    }

    public function lookup(Payment $payment): ?array
    {
        $answer = $payment->meta['test_answer'] ?? null;

        if (! in_array($answer, ['paid', 'failed'], true)) {
            return null;
        }

        return [
            'outcome' => $answer,
            'amount' => (float) $payment->amount,
            'ref' => 'TEST-' . substr($payment->tran_id, -8),
            'method' => 'test',
        ];
    }
}