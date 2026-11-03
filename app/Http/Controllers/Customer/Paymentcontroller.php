<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Payment;
use App\Services\Payments\PaymentGateways;
use App\Services\Payments\PaymentService;
use Illuminate\Http\Request;

/** The customer's side of paying a confirmed booking online. */
class PaymentController extends Controller
{
    public function __construct(private PaymentService $payments)
    {
    }

    /** "Pay online now": create a payment and send the customer to the provider's page. */
    public function start(Request $request, Booking $booking)
    {
        abort_unless($booking->customer_id === $request->user()->id, 404);

        $payment = $this->payments->start($booking);

        return redirect()->away(PaymentGateways::named($payment->provider)->checkoutUrl($payment));
    }

    /**
     * Where the provider sends the customer back. This page never decides whether
     * the payment worked: it asks the provider, then shows the booking.
     */
    public function back(Request $request, Payment $payment)
    {
        $booking = $this->ownBooking($request, $payment);

        $payment = $this->payments->verify($payment);

        $message = match ($payment->status) {
            'paid' => 'Payment received. Thank you!',
            'failed' => 'The payment did not go through. You have not been charged. You can try again or pay at pickup.',
            'refund_due', 'review' => 'We received your payment and the team will sort it out with you shortly.',
            default => 'We are still waiting for the payment to be confirmed. Refresh this page in a moment.',
        };

        return redirect()->route('bookings.show', $booking)->with($payment->status === 'paid' ? 'status' : 'payment_notice', $message);
    }

    /** The pretend payment page (test gateway only). */
    public function testShow(Request $request, Payment $payment)
    {
        $booking = $this->testPayment($request, $payment);

        return view('customer.payments.test', [
            'payment' => $payment,
            'booking' => $booking->load(['vehicle', 'business']),
        ]);
    }

    /** The buttons on the pretend payment page act like the provider's answer. */
    public function testAnswer(Request $request, Payment $payment, string $result)
    {
        $this->testPayment($request, $payment);
        abort_unless(in_array($result, ['paid', 'failed'], true), 404);

        if ($payment->status === 'pending') {
            $payment->forceFill(['meta' => array_merge($payment->meta ?? [], ['test_answer' => $result])])->save();
        }

        return redirect()->route('payments.back', $payment);
    }

    private function ownBooking(Request $request, Payment $payment): Booking
    {
        $booking = Booking::query()->findOrFail($payment->booking_id);
        abort_unless($booking->customer_id === $request->user()->id, 404);

        return $booking;
    }

    private function testPayment(Request $request, Payment $payment): Booking
    {
        abort_unless($payment->provider === 'test' && PaymentGateways::named('test') !== null, 404);

        return $this->ownBooking($request, $payment);
    }
}