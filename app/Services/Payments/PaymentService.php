<?php

namespace App\Services\Payments;

use App\Models\AdminLog;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Online payment rules. A payment is only ever marked paid here, and only after the
 * provider itself has been asked (lookup), never because a browser said so.
 */
class PaymentService
{
    /** Why this booking cannot be paid online right now, or null when it can. */
    public static function problem(Booking $booking): ?string
    {
        if (PaymentGateways::current() === null) {
            return 'Online payment is not available right now.';
        }
        if ($booking->status !== 'confirmed') {
            return $booking->status === 'pending'
                ? 'You can pay online once the shop confirms your booking.'
                : 'This booking can no longer be paid online.';
        }
        if ($booking->payment_status === 'paid') {
            return 'This booking is already paid.';
        }
        // start_datetime is a Cambodia wall-clock time, so compare it in Cambodia time.
        $start = Carbon::createFromFormat('Y-m-d H:i:s', $booking->start_datetime->format('Y-m-d H:i:s'), \App\Services\BookingService::TIMEZONE);
        if (Carbon::now(\App\Services\BookingService::TIMEZONE)->gte($start)) {
            return 'The pickup time has passed, so please pay the shop directly.';
        }
        if ((float) $booking->total_price <= 0) {
            return 'There is nothing to pay online for this booking.';
        }

        return null;
    }

    /** Begin a payment for a booking the customer owns. Older unfinished attempts are closed. */
    public function start(Booking $booking): Payment
    {
        $gateway = PaymentGateways::current();

        return DB::transaction(function () use ($booking, $gateway) {
            $b = Booking::query()->whereKey($booking->id)->lockForUpdate()->firstOrFail();

            if ($msg = self::problem($b)) {
                throw ValidationException::withMessages(['payment' => $msg]);
            }

            Payment::query()->where('booking_id', $b->id)->where('status', 'pending')
                ->update(['status' => 'expired', 'note' => 'Replaced by a newer payment attempt.']);

            return Payment::create([
                'booking_id' => $b->id,
                'business_id' => $b->business_id,
                'provider' => $gateway->name(),
                'amount' => $b->total_price,
                'currency' => config('payments.currency', 'USD'),
                'status' => 'pending',
            ]);
        });
    }

    /** Ask the provider what happened to this payment and record it. Safe to call many times. */
    public function verify(Payment $payment): Payment
    {
        $gateway = PaymentGateways::named($payment->provider);
        if (! $gateway || $payment->status !== 'pending') {
            return $payment->fresh();
        }

        $answer = $gateway->lookup($payment);
        if ($answer === null) {
            return $payment->fresh();
        }

        return $this->settle($payment, $answer['outcome'], (float) $answer['amount'], $answer['ref'] ?? null, $answer['method'] ?? null);
    }

    /** Record the provider's final answer. Idempotent: a payment settles once. */
    public function settle(Payment $payment, string $outcome, float $amount, ?string $ref = null, ?string $method = null): Payment
    {
        return DB::transaction(function () use ($payment, $outcome, $amount, $ref, $method) {
            $p = Payment::query()->whereKey($payment->id)->lockForUpdate()->firstOrFail();

            // Money states are final; never touch them again.
            if (in_array($p->status, ['paid', 'refund_due', 'refunded', 'review'], true)) {
                return $p;
            }

            if ($outcome !== 'paid') {
                if ($p->status === 'pending') {
                    $p->forceFill(['status' => 'failed', 'note' => 'The payment was not completed.'])->save();
                }

                return $p;
            }

            // Money really moved from here on.
            $b = Booking::query()->whereKey($p->booking_id)->lockForUpdate()->firstOrFail();
            $base = ['provider_ref' => $ref, 'method' => $method, 'paid_at' => Carbon::now()];

            if (abs($amount - (float) $p->amount) > 0.009) {
                $p->forceFill($base + ['status' => 'review', 'note' => sprintf('Provider reported %.2f but %.2f was expected.', $amount, (float) $p->amount)])->save();

                return $p;
            }

            if ($b->status === 'cancelled') {
                $p->forceFill($base + ['status' => 'refund_due', 'note' => 'Booking was cancelled before the payment arrived.'])->save();

                return $p;
            }

            if ($b->payment_status === 'paid') {
                $p->forceFill($base + ['status' => 'refund_due', 'note' => 'Booking was already marked paid, so this payment is a duplicate.'])->save();

                return $p;
            }

            $p->forceFill($base + ['status' => 'paid', 'note' => null])->save();
            $b->forceFill(['payment_status' => 'paid', 'payment_method' => 'online'])->save();

            return $p;
        });
    }

    /** Called from BookingWorkflow::cancel (already inside its transaction). */
    public static function bookingCancelled(Booking $booking): void
    {
        Payment::query()->where('booking_id', $booking->id)->where('status', 'pending')
            ->update(['status' => 'expired', 'note' => 'Booking was cancelled.']);

        Payment::query()->where('booking_id', $booking->id)->where('status', 'paid')
            ->update(['status' => 'refund_due', 'note' => 'Booking was cancelled after payment.']);
    }

    /** An admin confirms the money was sent back to the customer. */
    public function markRefunded(Payment $payment, ?User $admin, ?string $note = null): Payment
    {
        return DB::transaction(function () use ($payment, $admin, $note) {
            $p = Payment::query()->whereKey($payment->id)->lockForUpdate()->firstOrFail();

            if ($p->status !== 'refund_due') {
                throw ValidationException::withMessages(['payment' => 'Only a payment that is waiting for a refund can be marked as refunded.']);
            }

            $p->forceFill(['status' => 'refunded', 'refunded_at' => Carbon::now(), 'note' => $note ?: $p->note])->save();

            // The booking only becomes "refunded" if no other online payment on it is still holding money.
            $stillHeld = Payment::query()->where('booking_id', $p->booking_id)->where('status', 'paid')->exists();
            if (! $stillHeld) {
                Booking::query()->whereKey($p->booking_id)->where('payment_status', 'paid')
                    ->where('payment_method', 'online')->update(['payment_status' => 'refunded']);
            }

            AdminLog::record($admin, 'payment.refunded', sprintf('Marked %.2f %s refunded (%s)', (float) $p->amount, $p->currency, $p->tran_id), 'payment', $p->id, $note);

            return $p;
        });
    }
}