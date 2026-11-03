<?php

namespace App\Services;

use App\Models\Voucher;
use Illuminate\Support\Carbon;

/**
 * Checks a discount code against a rental. Never trusts the browser:
 * the discount is always recalculated here from the server-side subtotal.
 */
class VoucherService
{
    /**
     * @return array{ok:bool,message:?string,voucher:?Voucher,discount:float}
     */
    public function check(mixed $code, string $businessId, float $subtotal, ?Carbon $now = null): array
    {
        $now ??= Carbon::now();

        if (! is_string($code) || trim($code) === '') {
            return $this->fail('Please enter a voucher code.');
        }

        $voucher = Voucher::query()->where('code', strtoupper(trim($code)))->first();

        if (! $voucher || ! $voucher->is_active) {
            return $this->fail("That voucher code isn't valid.");
        }

        if ($voucher->business_id !== null && $voucher->business_id !== $businessId) {
            return $this->fail("That voucher can't be used with this shop.");
        }

        if ($voucher->starts_at && $voucher->starts_at->gt($now)) {
            return $this->fail("That voucher isn't active yet.");
        }

        if ($voucher->expires_at && $voucher->expires_at->lte($now)) {
            return $this->fail('That voucher has expired.');
        }

        if ($voucher->max_uses !== null && $voucher->used_count >= $voucher->max_uses) {
            return $this->fail('That voucher has been fully used.');
        }

        if ($subtotal < (float) $voucher->min_subtotal) {
            return $this->fail('This voucher needs a rental of at least $' . number_format((float) $voucher->min_subtotal, 2) . '.');
        }

        $discount = $voucher->type === 'percent'
            ? round($subtotal * (float) $voucher->value / 100, 2)
            : (float) $voucher->value;

        $discount = round(min($discount, $subtotal), 2);

        return ['ok' => true, 'message' => null, 'voucher' => $voucher, 'discount' => $discount];
    }

    private function fail(string $message): array
    {
        return ['ok' => false, 'message' => $message, 'voucher' => null, 'discount' => 0.0];
    }
}