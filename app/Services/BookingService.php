<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleBlock;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * All the rules for a customer booking a vehicle.
 *
 * Rental dates are wall-clock times in Cambodia (Asia/Phnom_Penh) and are
 * stored exactly like that in start_datetime / end_datetime — the same
 * convention the business dashboard uses.
 */
class BookingService
{
    public const TIMEZONE = 'Asia/Phnom_Penh';

    /** Bookings in these statuses keep the vehicle blocked for their dates. */
    public const BLOCKING_STATUSES = ['pending', 'confirmed', 'ongoing'];

    public const MAX_DAYS = 30;

    /** A pending request holds the vehicle this long, then expires. */
    public const HOLD_MINUTES = 120;

    /** Payment choices a customer can pick at checkout (online comes later). */
    public const PAYMENT_METHODS = ['pay_at_pickup'];

    public function __construct(private ?VoucherService $vouchers = null)
    {
        $this->vouchers ??= new VoucherService();
    }

    /** Earliest pickup is this many minutes from now. */
    public const LEAD_MINUTES = 30;

    /**
     * Turn the four form values into a [start, end] pair, or throw a
     * ValidationException with a friendly message.
     *
     * @return array{0:Carbon,1:Carbon}
     */
    public function parseWindow(mixed $pickupDate, mixed $pickupTime, mixed $returnDate, mixed $returnTime): array
    {
        $start = $this->parseLocal($pickupDate, $pickupTime, 'pickup');
        $end = $this->parseLocal($returnDate, $returnTime, 'return');

        $now = Carbon::now(self::TIMEZONE);

        if ($start->lt($now->copy()->addMinutes(self::LEAD_MINUTES))) {
            throw ValidationException::withMessages([
                'pickup_date' => 'Pickup time must be in the future.',
            ]);
        }

        if ($end->lte($start)) {
            throw ValidationException::withMessages([
                'return_date' => 'Return must be after pickup.',
            ]);
        }

        if ($start->diffInHours($end) > self::MAX_DAYS * 24) {
            throw ValidationException::withMessages([
                'return_date' => 'Bookings can be at most ' . self::MAX_DAYS . ' days. Please contact the shop for longer rentals.',
            ]);
        }

        return [$start, $end];
    }

    private function parseLocal(mixed $date, mixed $time, string $field): Carbon
    {
        $time = (is_string($time) && $time !== '') ? $time : '09:00';

        if (! is_string($date)
            || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)
            || ! preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $time)) {
            throw ValidationException::withMessages([
                "{$field}_date" => 'Please choose a valid ' . $field . ' date and time.',
            ]);
        }

        try {
            $parsed = Carbon::createFromFormat('Y-m-d H:i', "{$date} {$time}", self::TIMEZONE);
        } catch (\Throwable) {
            $parsed = false;
        }

        // createFromFormat happily rolls 2026-02-31 over to March; reject that.
        if (! $parsed || $parsed->format('Y-m-d') !== $date) {
            throw ValidationException::withMessages([
                "{$field}_date" => 'Please choose a valid ' . $field . ' date and time.',
            ]);
        }

        return $parsed;
    }

    /**
     * Price is calculated on the server from the vehicle's own daily rate:
     * every started 24 hours counts as a day (minimum one day).
     *
     * @param  mixed  $voucherCode  optional discount code typed by the customer
     * @return array{days:int,rate:float,subtotal:float,discount:float,total:float,deposit:float,voucher:?string,voucher_ok:?bool,voucher_message:?string}
     */
    public function quote(Vehicle $vehicle, Carbon $start, Carbon $end, mixed $voucherCode = null): array
    {
        $hours = max(1, $start->diffInMinutes($end) / 60);
        $days = max(1, (int) ceil($hours / 24));
        $rate = (float) $vehicle->price_per_day;
        $subtotal = round($days * $rate, 2);

        $discount = 0.0;
        $voucher = null;
        $voucherOk = null;
        $voucherMessage = null;

        if (is_string($voucherCode) && trim($voucherCode) !== '') {
            $check = $this->vouchers->check($voucherCode, $vehicle->business_id, $subtotal);
            $voucherOk = $check['ok'];
            $voucherMessage = $check['message'];
            if ($check['ok']) {
                $discount = $check['discount'];
                $voucher = $check['voucher']->code;
            }
        }

        return [
            'days' => $days,
            'rate' => $rate,
            'subtotal' => $subtotal,
            'discount' => $discount,
            'total' => round($subtotal - $discount, 2),
            'deposit' => round((float) $vehicle->deposit_amount, 2),
            'voucher' => $voucher,
            'voucher_ok' => $voucherOk,
            'voucher_message' => $voucherMessage,
        ];
    }

    /**
     * Pending requests that nobody answered in time stop holding the vehicle
     * and are marked as expired. Safe to call often.
     */
    public function expireStale(): int
    {
        $stale = Booking::query()
            ->where('status', 'pending')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', Carbon::now()->toDateTimeString())
            ->get();

        foreach ($stale as $booking) {
            (new BookingWorkflow($this->vouchers))->cancel($booking, 'system', 'No response from the shop in time.');
        }

        return $stale->count();
    }

    /** Can this vehicle be rented at all, and is the window free? */
    public function isAvailable(Vehicle $vehicle, Carbon $start, Carbon $end): bool
    {
        if ($vehicle->status !== 'available') {
            return false;
        }

        $this->expireStale();

        return ! $this->overlapping($vehicle->id, $start, $end)->exists()
            && ! $this->blocksOverlapping($vehicle->id, $start, $end)->exists();
    }

    /** Days the shop took off the calendar that touch this window. */
    public function blocksOverlapping(string|array|null $vehicleId, Carbon $start, Carbon $end)
    {
        return VehicleBlock::query()
            ->when(is_array($vehicleId), fn ($q) => $q->whereIn('vehicle_id', $vehicleId))
            ->when(is_string($vehicleId), fn ($q) => $q->where('vehicle_id', $vehicleId))
            ->where('start_datetime', '<', $end->toDateTimeString())
            ->where('end_datetime', '>', $start->toDateTimeString());
    }

    /** Bookings that hold the vehicle(s) during this window (for the shop calendar). */
    public function blockingBookings(string|array|null $vehicleId, Carbon $start, Carbon $end)
    {
        $this->expireStale();

        return $this->overlapping($vehicleId, $start, $end);
    }

    /**
     * Bookings that still hold this vehicle from now on (confirmed, ongoing, or
     * a pending request that has not expired). Used before taking a vehicle
     * out of service.
     */
    public function activeBookingsFor(Vehicle $vehicle)
    {
        $this->expireStale();

        $now = Carbon::now(self::TIMEZONE);

        return $this->overlapping($vehicle->id, $now, $now->copy()->addYears(5));
    }

    /** Ids of every vehicle that is already taken during this window. */
    public function unavailableVehicleIds(Carbon $start, Carbon $end): array
    {
        $this->expireStale();

        return $this->overlapping(null, $start, $end)->pluck('vehicle_id')
            ->merge($this->blocksOverlapping(null, $start, $end)->pluck('vehicle_id'))
            ->unique()->values()->all();
    }

    private function overlapping(string|array|null $vehicleId, Carbon $start, Carbon $end)
    {
        return Booking::query()
            ->when(is_array($vehicleId), fn ($q) => $q->whereIn('vehicle_id', $vehicleId))
            ->when(is_string($vehicleId), fn ($q) => $q->where('vehicle_id', $vehicleId))
            ->where(function ($q) {
                $q->whereIn('status', ['confirmed', 'ongoing'])
                    ->orWhere(function ($q) {
                        // a pending request holds the vehicle only until it expires
                        $q->where('status', 'pending')
                            ->where(function ($q) {
                                $q->whereNull('expires_at')
                                    ->orWhere('expires_at', '>', Carbon::now()->toDateTimeString());
                            });
                    });
            })
            ->where('start_datetime', '<', $end->toDateTimeString())
            ->where('end_datetime', '>', $start->toDateTimeString());
    }

    /**
     * Normalise a phone number to digits with an optional leading +.
     * Returns null when it does not look like a phone number.
     */
    public function normalizePhone(mixed $phone): ?string
    {
        if (! is_string($phone)) {
            return null;
        }

        $phone = trim($phone);
        $plus = str_starts_with($phone, '+') ? '+' : '';
        $digits = preg_replace('/\D+/', '', $phone);

        if ($digits === '' || strlen($digits) < 8 || strlen($digits) > 15) {
            return null;
        }

        return $plus . $digits;
    }

    /**
     * The same Cambodian number can be typed several ways (012 345 678,
     * +855 12 345 678, 85512345678). Every stored form it could have, so
     * duplicate checks and sign-in match whichever way it was entered.
     *
     * @return list<string>
     */
    public function phoneVariants(?string $phone): array
    {
        $normalized = $this->normalizePhone($phone);

        if ($normalized === null) {
            return [];
        }

        $digits = ltrim($normalized, '+');
        $local = str_starts_with($digits, '855') ? '0' . substr($digits, 3) : (str_starts_with($digits, '0') ? $digits : null);
        $national = $local !== null ? substr($local, 1) : null;

        return array_values(array_unique(array_filter([
            $normalized,
            $local,
            $national !== null ? '855' . $national : null,
            $national !== null ? '+855' . $national : null,
        ])));
    }

    /**
     * Create a pending booking for a signed-in customer.
     *
     * @param  array{email?:?string,notes?:?string,voucher_code?:?string,payment_method?:?string}  $options
     */
    public function createBooking(User $customer, Vehicle $vehicle, Carbon $start, Carbon $end, array $options = []): Booking
    {
        return $this->persist($vehicle, $start, $end, $options, $customer);
    }

    /**
     * Create a pending booking for someone identified only by name + phone
     * (finds or creates a light account without a usable password).
     * The website itself now always books for a signed-in customer;
     * this stays for seeders, imports and tests.
     *
     * @param  array{name:string,phone:string,email?:?string,notes?:?string,voucher_code?:?string,payment_method?:?string}  $customer
     */
    public function createGuestBooking(Vehicle $vehicle, Carbon $start, Carbon $end, array $customer): Booking
    {
        return $this->persist($vehicle, $start, $end, $customer, null);
    }

    /** Sign-up: one account per phone number. */
    public function registerCustomer(string $name, string $phone, string $password, ?string $email = null): User
    {
        $normalized = $this->normalizePhone($phone);

        if ($normalized === null) {
            throw ValidationException::withMessages(['phone' => 'Please enter a valid phone number.']);
        }

        if (User::query()->whereIn('phone', $this->phoneVariants($normalized))->exists()) {
            throw ValidationException::withMessages([
                'phone' => 'An account with this phone number already exists. Please sign in instead.',
            ]);
        }

        if (filled($email) && User::query()->where('email', $email)->exists()) {
            throw ValidationException::withMessages([
                'email' => 'An account with this email already exists. Please sign in instead.',
            ]);
        }

        return User::create([
            'full_name' => $name,
            'phone' => $normalized,
            'email' => filled($email) ? $email : null,
            'role' => 'customer',
            'password_hash' => Hash::make($password),
            'is_active' => true,
        ]);
    }

    private function persist(Vehicle $vehicle, Carbon $start, Carbon $end, array $customer, ?User $account): Booking
    {
        $phone = null;

        if ($account === null) {
            $phone = $this->normalizePhone($customer['phone'] ?? null);

            if ($phone === null) {
                throw ValidationException::withMessages([
                    'phone' => 'Please enter a valid phone number.',
                ]);
            }
        }

        $method = $customer['payment_method'] ?? self::PAYMENT_METHODS[0];
        if (! in_array($method, self::PAYMENT_METHODS, true)) {
            throw ValidationException::withMessages([
                'payment_method' => 'Please choose an available payment method.',
            ]);
        }

        $attempts = 0;

        while (true) {
            try {
                return DB::transaction(function () use ($vehicle, $start, $end, $customer, $phone, $method, $account) {
                    // Lock the vehicle row so two people cannot take the same dates.
                    $locked = Vehicle::query()->whereKey($vehicle->id)->lockForUpdate()->firstOrFail();

                    if ($locked->status !== 'available') {
                        throw ValidationException::withMessages([
                            'vehicle' => 'Sorry, this vehicle is not available for booking right now.',
                        ]);
                    }

                    $this->expireStale();

                    if ($this->overlapping($locked->id, $start, $end)->exists()) {
                        throw ValidationException::withMessages([
                            'pickup_date' => 'Sorry, this vehicle is already booked for those dates. Please try different dates.',
                        ]);
                    }

                    if ($this->blocksOverlapping($locked->id, $start, $end)->exists()) {
                        throw ValidationException::withMessages([
                            'pickup_date' => 'Sorry, this vehicle is not available for those dates. Please try different dates.',
                        ]);
                    }

                    $quote = $this->quote($locked, $start, $end, $customer['voucher_code'] ?? null);

                    // A code that was typed but is not valid must stop the booking,
                    // otherwise the customer would pay more than the page promised.
                    if ($quote['voucher_ok'] === false) {
                        throw ValidationException::withMessages(['voucher_code' => $quote['voucher_message']]);
                    }

                    if ($quote['voucher']) {
                        // Count the use; the row lock stops two people sharing the last use.
                        $voucher = \App\Models\Voucher::query()->where('code', $quote['voucher'])->lockForUpdate()->first();
                        $recheck = $this->vouchers->check($quote['voucher'], $locked->business_id, $quote['subtotal']);
                        if (! $recheck['ok']) {
                            throw ValidationException::withMessages(['voucher_code' => $recheck['message']]);
                        }
                        $voucher->increment('used_count');
                    }

                    $user = $account ?? $this->guestUser($customer['name'], $phone);
                    $locked->loadMissing('business');

                    return Booking::create([
                        'reference' => $this->nextReference(),
                        'customer_id' => $user->id,
                        'vehicle_id' => $locked->id,
                        'business_id' => $locked->business_id,
                        'start_datetime' => $start->toDateTimeString(),
                        'end_datetime' => $end->toDateTimeString(),
                        'pickup_location' => $locked->pick_up_location
                            ?: ($locked->business->address ?: ($locked->business->city ?: 'Siem Reap')),
                        'status' => 'pending',
                        'rate_type' => 'daily',
                        'rate_amount' => $quote['rate'],
                        'total_price' => $quote['total'],
                        'discount_amount' => $quote['discount'],
                        'voucher_code' => $quote['voucher'],
                        'deposit_amount' => $quote['deposit'],
                        'payment_status' => 'unpaid',
                        'payment_method' => $method,
                        'customer_email' => filled($customer['email'] ?? null) ? $customer['email'] : null,
                        'customer_notes' => filled($customer['notes'] ?? null) ? $customer['notes'] : null,
                        'expires_at' => Carbon::now()->addMinutes(self::HOLD_MINUTES)->toDateTimeString(),
                        'terms_accepted_at' => Carbon::now()->toDateTimeString(),
                    ]);
                });
            } catch (QueryException $e) {
                // Two bookings picked the same reference at the same moment: retry.
                if (++$attempts < 5 && str_contains(strtolower($e->getMessage()), 'reference')) {
                    continue;
                }
                throw $e;
            }
        }
    }

    /** Find the customer by phone, or create a light guest account. */
    private function guestUser(string $name, string $phone): User
    {
        $user = User::query()->where('phone', $phone)->first();

        if ($user) {
            if ($user->role !== 'customer') {
                throw ValidationException::withMessages([
                    'phone' => 'This phone number belongs to a business account. Please use a different number.',
                ]);
            }

            return $user;
        }

        return User::create([
            'full_name' => $name,
            'phone' => $phone,
            'role' => 'customer',
            'password_hash' => Hash::make(Str::random(40)), // guest: no usable password
            'is_active' => true,
        ]);
    }

    /** AR-000123 style, one higher than the biggest existing number. */
    private function nextReference(): string
    {
        $last = Booking::query()
            ->whereNotNull('reference')
            ->orderByDesc('reference')
            ->value('reference');

        $number = $last ? ((int) substr($last, 3)) + 1 : 1;

        return sprintf('AR-%06d', $number);
    }
}