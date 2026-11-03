<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Vehicle;
use App\Models\Voucher;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Every status change of a booking lives here, so the rules are the same
 * whether the customer or the shop triggers them.
 *
 *   pending ──► confirmed ──► ongoing ──► completed
 *      │            │
 *      └────────────┴──► cancelled   (rejected / cancelled / expired)
 */
class BookingWorkflow
{
    /** A customer may cancel a confirmed booking until this many hours before pickup. */
    public const FREE_CANCEL_HOURS = 24;

    public function __construct(private ?VoucherService $vouchers = null)
    {
        $this->vouchers ??= new VoucherService();
    }

    // ------------------------------------------------------------------
    // Customer side
    // ------------------------------------------------------------------

    /** Can the customer still cancel this booking, and why not? */
    public function customerCancelProblem(Booking $booking): ?string
    {
        if ($booking->status === 'pending') {
            return null;
        }

        if ($booking->status === 'confirmed') {
            // start_datetime is a Cambodia wall-clock time, so compare it in Cambodia time.
            $start = Carbon::createFromFormat('Y-m-d H:i:s', $booking->start_datetime->format('Y-m-d H:i:s'), BookingService::TIMEZONE);
            if (Carbon::now(BookingService::TIMEZONE)->gt($start->subHours(self::FREE_CANCEL_HOURS))) {
                return 'This booking can only be cancelled up to ' . self::FREE_CANCEL_HOURS . ' hours before pickup. Please call the shop.';
            }
            return null;
        }

        return 'This booking can no longer be cancelled.';
    }

    public function customerCancel(Booking $booking, ?string $reason = null): Booking
    {
        $problem = $this->customerCancelProblem($booking);
        if ($problem) {
            throw ValidationException::withMessages(['booking' => $problem]);
        }

        return $this->cancel($booking, 'customer', $reason ?: 'Cancelled by the customer.');
    }

    // ------------------------------------------------------------------
    // Shop side
    // ------------------------------------------------------------------

    public function confirm(Booking $booking): Booking
    {
        return DB::transaction(function () use ($booking) {
            $b = $this->lock($booking);
            $this->need($b, ['pending'], 'Only a pending request can be confirmed.');

            if ($b->expires_at && $b->expires_at->lte(Carbon::now())) {
                $this->cancel($b, 'system', 'No response from the shop in time.');
                throw ValidationException::withMessages(['booking' => 'This request has expired. The customer must book again.']);
            }

            // Another booking may have been confirmed for these dates in the meantime.
            $clash = Booking::query()
                ->where('vehicle_id', $b->vehicle_id)
                ->where('id', '!=', $b->id)
                ->whereIn('status', ['confirmed', 'ongoing'])
                ->where('start_datetime', '<', $b->end_datetime->toDateTimeString())
                ->where('end_datetime', '>', $b->start_datetime->toDateTimeString())
                ->exists();

            if ($clash) {
                throw ValidationException::withMessages(['booking' => 'This vehicle already has a confirmed booking for those dates.']);
            }

            $b->forceFill(['status' => 'confirmed', 'confirmed_at' => Carbon::now(), 'expires_at' => null])->save();

            return $b;
        });
    }

    public function reject(Booking $booking, ?string $reason = null): Booking
    {
        $b = $booking->fresh();
        $this->need($b, ['pending'], 'Only a pending request can be rejected.');

        return $this->cancel($b, 'business', $reason ?: 'The shop could not accept this booking.');
    }

    /** Customer arrived and took the vehicle. */
    public function pickedUp(Booking $booking): Booking
    {
        return DB::transaction(function () use ($booking) {
            $b = $this->lock($booking);
            $this->need($b, ['confirmed'], 'Only a confirmed booking can be handed over.');

            $b->forceFill(['status' => 'ongoing', 'picked_up_at' => Carbon::now()])->save();
            Vehicle::query()->whereKey($b->vehicle_id)->where('status', 'available')->update(['status' => 'rented']);

            return $b;
        });
    }

    /** Vehicle came back. */
    public function returned(Booking $booking): Booking
    {
        return DB::transaction(function () use ($booking) {
            $b = $this->lock($booking);
            $this->need($b, ['ongoing'], 'Only an ongoing rental can be marked as returned.');

            $b->forceFill(['status' => 'completed', 'returned_at' => Carbon::now()])->save();

            $stillOut = Booking::query()->where('vehicle_id', $b->vehicle_id)
                ->where('id', '!=', $b->id)->where('status', 'ongoing')->exists();
            if (! $stillOut) {
                Vehicle::query()->whereKey($b->vehicle_id)->where('status', 'rented')->update(['status' => 'available']);
            }

            return $b;
        });
    }

    /** The customer paid the shop (cash at pickup). */
    public function markPaid(Booking $booking): Booking
    {
        $b = $booking->fresh();
        $this->need($b, ['confirmed', 'ongoing', 'completed'], 'Only a confirmed booking can be marked as paid.');

        if ($b->payment_status === 'paid') {
            return $b;
        }

        $b->forceFill(['payment_status' => 'paid'])->save();

        return $b;
    }

    // ------------------------------------------------------------------
    // Shared
    // ------------------------------------------------------------------

    /** Reject, cancel and expire all end here. Gives a used voucher back. */
    public function cancel(Booking $booking, string $by, string $reason): Booking
    {
        return DB::transaction(function () use ($booking, $by, $reason) {
            $b = $this->lock($booking);

            if (! in_array($b->status, ['pending', 'confirmed'], true)) {
                return $b;
            }

            $b->forceFill([
                'status' => 'cancelled',
                'cancelled_by' => $by,
                'cancelled_at' => Carbon::now(),
                'cancellation_reason' => $reason,
                'expires_at' => null,
            ])->save();

            if ($b->voucher_code) {
                Voucher::query()->where('code', $b->voucher_code)->where('used_count', '>', 0)->decrement('used_count');
            }

            return $b;
        });
    }

    private function lock(Booking $booking): Booking
    {
        return Booking::query()->whereKey($booking->id)->lockForUpdate()->firstOrFail();
    }

    private function need(Booking $booking, array $statuses, string $message): void
    {
        if (! in_array($booking->status, $statuses, true)) {
            throw ValidationException::withMessages(['booking' => $message]);
        }
    }
}