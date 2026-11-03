<?php

namespace App\Support;

use App\Models\Booking;
use App\Models\Voucher;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Shared rules for creating / editing vouchers, used by the shop portal
 * (shop codes) and the admin (platform-wide codes). Checking a code at
 * checkout is still done by App\Services\VoucherService.
 */
class VoucherForm
{
    public const TZ = 'Asia/Phnom_Penh';

    /** Validate the form and return attributes ready for Voucher::fill(). */
    public static function validate(Request $request, ?Voucher $voucher = null): array
    {
        $input = $request->all();
        $locked = $voucher && $voucher->used_count > 0;

        // The code can't change once customers have used it.
        $code = $locked ? $voucher->code : strtoupper(trim((string) ($input['code'] ?? '')));
        $input['code'] = $code;

        $data = Validator::make($input, [
            'code' => ['required', 'string', 'min:3', 'max:20', 'regex:/^[A-Z0-9_-]+$/'],
            'description' => ['nullable', 'string', 'max:120'],
            'type' => ['required', 'in:percent,fixed'],
            'value' => ['required', 'numeric'],
            'min_subtotal' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'max_uses' => ['nullable', 'integer', 'min:1', 'max:1000000'],
            'starts_on' => ['nullable', 'date_format:Y-m-d'],
            'ends_on' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:starts_on'],
        ], [
            'code.required' => 'Enter a code customers will type, for example WELCOME10.',
            'code.min' => 'The code needs at least 3 characters.',
            'code.max' => 'The code can be at most 20 characters.',
            'code.regex' => 'Use only letters, numbers, dashes and underscores in the code.',
            'type.in' => 'Choose a percentage or a fixed amount.',
            'value.required' => 'Enter how much the voucher takes off.',
            'value.numeric' => 'The discount must be a number.',
            'min_subtotal.numeric' => 'The minimum booking must be a number.',
            'min_subtotal.min' => 'The minimum booking cannot be negative.',
            'max_uses.integer' => 'The usage limit must be a whole number.',
            'max_uses.min' => 'The usage limit must be at least 1.',
            'starts_on.date_format' => 'Enter the start date as a date.',
            'ends_on.date_format' => 'Enter the end date as a date.',
            'ends_on.after_or_equal' => 'The end date cannot be before the start date.',
        ])->validate();

        $errors = [];
        $value = (float) $data['value'];

        if ($data['type'] === 'percent' && ($value < 1 || $value > 100)) {
            $errors['value'] = 'A percentage must be between 1 and 100.';
        } elseif ($data['type'] === 'fixed' && ($value <= 0 || $value > 1000)) {
            $errors['value'] = 'A fixed amount must be more than $0 and at most $1,000.';
        }

        if (! $voucher && ! empty($data['ends_on']) && $data['ends_on'] < Carbon::now(self::TZ)->toDateString()) {
            $errors['ends_on'] = 'The end date is already in the past.';
        }

        $maxUses = isset($data['max_uses']) && $data['max_uses'] !== '' ? (int) $data['max_uses'] : null;
        if ($voucher && $maxUses !== null && $maxUses < $voucher->used_count) {
            $errors['max_uses'] = "This code has already been used {$voucher->used_count} times, so the limit cannot be lower than that.";
        }

        $taken = Voucher::query()->where('code', $code)->when($voucher, fn ($q) => $q->where('id', '!=', $voucher->id))->exists();
        if ($taken) {
            $errors['code'] = 'That code is already in use. Please choose another.';
        }

        if ($errors) {
            throw ValidationException::withMessages($errors);
        }

        $appTz = config('app.timezone', 'UTC');

        return [
            'code' => $code,
            'description' => filled($data['description'] ?? null) ? trim($data['description']) : null,
            'type' => $data['type'],
            'value' => round($value, 2),
            'min_subtotal' => round((float) ($data['min_subtotal'] ?? 0), 2),
            'max_uses' => $maxUses,
            // Dates are Cambodian calendar days: from 00:00 on the first day to the end of the last day.
            'starts_at' => filled($data['starts_on'] ?? null) ? Carbon::createFromFormat('Y-m-d', $data['starts_on'], self::TZ)->startOfDay()->setTimezone($appTz) : null,
            'expires_at' => filled($data['ends_on'] ?? null) ? Carbon::createFromFormat('Y-m-d', $data['ends_on'], self::TZ)->endOfDay()->setTimezone($appTz) : null,
            'is_active' => filter_var($input['is_active'] ?? false, FILTER_VALIDATE_BOOLEAN),
        ];
    }

    /** Values for the form fields (a blank form, or an existing voucher). */
    public static function values(?Voucher $v): array
    {
        return [
            'code' => $v?->code,
            'description' => $v?->description,
            'type' => $v?->type ?? 'percent',
            'value' => $v ? rtrim(rtrim(number_format((float) $v->value, 2, '.', ''), '0'), '.') : null,
            'min_subtotal' => $v && (float) $v->min_subtotal > 0 ? rtrim(rtrim(number_format((float) $v->min_subtotal, 2, '.', ''), '0'), '.') : null,
            'max_uses' => $v?->max_uses,
            'starts_on' => $v?->starts_at?->copy()->setTimezone(self::TZ)->format('Y-m-d'),
            'ends_on' => $v?->expires_at?->copy()->setTimezone(self::TZ)->format('Y-m-d'),
            'is_active' => $v ? $v->is_active : true,
        ];
    }

    /** [key, label] where key is one of active, paused, scheduled, expired, used_up. */
    public static function status(Voucher $v): array
    {
        $now = Carbon::now();

        return match (true) {
            ! $v->is_active => ['paused', 'Paused'],
            $v->expires_at && $v->expires_at->lte($now) => ['expired', 'Expired'],
            $v->max_uses !== null && $v->used_count >= $v->max_uses => ['used_up', 'Used up'],
            $v->starts_at && $v->starts_at->gt($now) => ['scheduled', 'Starts ' . $v->starts_at->copy()->setTimezone(self::TZ)->format('M j')],
            default => ['active', 'Active'],
        };
    }

    /** "10% off" or "$5 off". */
    public static function offer(Voucher $v): string
    {
        $n = rtrim(rtrim(number_format((float) $v->value, 2), '0'), '.');

        return $v->type === 'percent' ? "{$n}% off" : "\${$n} off";
    }

    /** Adds ->discount_given and ->rentals to each voucher, from rentals that really went ahead. */
    public static function withUsage(Collection $vouchers): Collection
    {
        if ($vouchers->isEmpty()) {
            return $vouchers;
        }

        $rows = Booking::query()
            ->whereIn('voucher_code', $vouchers->pluck('code'))
            ->whereIn('status', ['confirmed', 'ongoing', 'completed'])
            ->selectRaw('voucher_code, count(*) as rentals, sum(discount_amount) as given')
            ->groupBy('voucher_code')->get()->keyBy('voucher_code');

        return $vouchers->each(function (Voucher $v) use ($rows) {
            $v->rentals = (int) ($rows[$v->code]->rentals ?? 0);
            $v->discount_given = (float) ($rows[$v->code]->given ?? 0);
        });
    }

    /** Has any booking ever used this code? (Then it can be paused but not deleted.) */
    public static function everUsed(Voucher $v): bool
    {
        return $v->used_count > 0 || Booking::where('voucher_code', $v->code)->exists();
    }
}