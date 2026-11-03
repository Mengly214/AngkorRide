<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use App\Models\VehicleBlock;
use App\Services\BookingService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class AvailabilityController extends Controller
{
    private const MAX_BLOCK_DAYS = 120;

    public function __construct(private BookingService $bookings)
    {
    }

    public function index(Request $request)
    {
        $business = $this->business($request);

        $vehicles = $business->vehicles()
            ->where('status', '!=', 'retired')
            ->orderBy('brand')->orderBy('model')->get();

        $tz = BookingService::TIMEZONE;
        $today = Carbon::now($tz)->startOfDay();

        $month = $this->parseMonth($request->query('m'), $today);
        $monthEnd = $month->copy()->endOfMonth()->startOfDay();

        $selected = $vehicles->firstWhere('id', $request->query('vehicle'));

        $shown = $selected ? collect([$selected]) : $vehicles;
        $map = $this->dayMap($shown->pluck('id')->all(), $month, $monthEnd);

        // Upcoming blocks across the shop, for the "remove" list.
        $upcoming = VehicleBlock::query()
            ->whereIn('vehicle_id', $vehicles->pluck('id'))
            ->where('end_datetime', '>', $today->toDateTimeString())
            ->with('vehicle:id,brand,model,plate_number')
            ->orderBy('start_datetime')
            ->get();

        return view('portal.availability.index', [
            'business' => $business,
            'vehicles' => $vehicles,
            'selected' => $selected,
            'month' => $month,
            'monthEnd' => $monthEnd,
            'prevMonth' => $month->copy()->subMonthNoOverflow()->format('Y-m'),
            'nextMonth' => $month->copy()->addMonthNoOverflow()->format('Y-m'),
            'thisMonth' => $today->format('Y-m'),
            'today' => $today,
            'map' => $map['days'],
            'entries' => $map['entries'],
            'upcomingBlocks' => $upcoming,
        ]);
    }

    public function store(Request $request)
    {
        $business = $this->business($request);
        $today = Carbon::now(BookingService::TIMEZONE)->startOfDay();

        $data = $request->validate([
            'vehicle_id' => 'required|string',
            'from' => 'required|date_format:Y-m-d',
            'to' => 'required|date_format:Y-m-d|after_or_equal:from',
            'reason' => 'required|in:' . implode(',', array_keys(VehicleBlock::REASONS)),
            'note' => 'nullable|string|max:120',
        ], [
            'from.required' => 'Please choose the first day to block.',
            'to.required' => 'Please choose the last day to block.',
            'to.after_or_equal' => 'The last day cannot be before the first day.',
        ]);

        $vehicle = $business->vehicles()->where('status', '!=', 'retired')->find($data['vehicle_id']);
        if (! $vehicle) {
            throw ValidationException::withMessages(['vehicle_id' => 'Please choose one of your vehicles.']);
        }

        $start = Carbon::createFromFormat('Y-m-d', $data['from'], BookingService::TIMEZONE)->startOfDay();
        $last = Carbon::createFromFormat('Y-m-d', $data['to'], BookingService::TIMEZONE)->startOfDay();
        $end = $last->copy()->addDay();

        if ($last->lt($today)) {
            throw ValidationException::withMessages(['from' => 'You can only block today or later.']);
        }
        if ($start->diffInDays($end) > self::MAX_BLOCK_DAYS) {
            throw ValidationException::withMessages(['to' => 'You can block up to ' . self::MAX_BLOCK_DAYS . ' days at a time.']);
        }

        $clash = $this->bookings->blockingBookings($vehicle->id, $start, $end)->get();
        if ($clash->isNotEmpty()) {
            $refs = $clash->map(fn ($b) => $b->reference ?: 'a booking')->take(4)->implode(', ');
            throw ValidationException::withMessages([
                'from' => 'These dates already have ' . ($clash->count() === 1 ? 'a booking' : $clash->count() . ' bookings')
                    . " ({$refs}). Complete or cancel " . ($clash->count() === 1 ? 'it' : 'them') . ' in Bookings first, or pick other days.',
            ]);
        }

        if ($this->bookings->blocksOverlapping($vehicle->id, $start, $end)->exists()) {
            throw ValidationException::withMessages(['from' => 'Some of these days are already blocked for this vehicle.']);
        }

        VehicleBlock::create([
            'vehicle_id' => $vehicle->id,
            'start_datetime' => $start,
            'end_datetime' => $end,
            'reason' => $data['reason'],
            'note' => $data['note'] ?? null,
        ]);

        return redirect()
            ->route('portal.availability.index', ['vehicle' => $vehicle->id, 'm' => $start->format('Y-m')])
            ->with('status', 'Blocked ' . trim($vehicle->brand . ' ' . $vehicle->model) . ' for ' . $this->rangeLabel($start, $last) . '. Customers can no longer book those days.');
    }

    public function destroy(Request $request, VehicleBlock $block)
    {
        $business = $this->business($request);

        $vehicle = Vehicle::find($block->vehicle_id);
        abort_unless($vehicle && $vehicle->business_id === $business->id, 404);

        $label = $this->rangeLabel($block->start_datetime, $block->lastDay());
        $block->delete();

        return redirect()
            ->route('portal.availability.index', ['vehicle' => $vehicle->id, 'm' => $block->start_datetime->format('Y-m')])
            ->with('status', "Unblocked {$label}. Those days can be booked again.");
    }

    /**
     * For each vehicle and day of the month: what holds it.
     *
     * @return array{days: array<string, array<int, array>>, entries: array<string, list<array>>}
     */
    private function dayMap(array $vehicleIds, Carbon $month, Carbon $monthEnd): array
    {
        $days = [];
        $entries = [];

        if ($vehicleIds === []) {
            return ['days' => $days, 'entries' => $entries];
        }

        $windowEnd = $monthEnd->copy()->addDay();
        $rank = ['ongoing' => 3, 'confirmed' => 3, 'pending' => 2, 'blocked' => 1];

        $put = function (string $vehicleId, array $entry) use (&$days, &$entries, $month, $monthEnd, $rank) {
            $first = $entry['start']->copy()->startOfDay();
            $last = $entry['end']->copy()->subSecond()->startOfDay();
            $from = $first->lt($month) ? $month->copy() : $first;
            $to = $last->gt($monthEnd) ? $monthEnd->copy() : $last;

            $entry['first_day'] = $first->format('Y-m-d');
            $entry['last_day'] = $last->format('Y-m-d');
            $entries[$vehicleId][] = $entry;

            for ($d = $from->copy(); $d->lte($to); $d->addDay()) {
                $n = (int) $d->format('j');
                $current = $days[$vehicleId][$n] ?? null;
                if ($current === null || $rank[$entry['kind']] > $rank[$current['kind']]) {
                    $days[$vehicleId][$n] = $entry + [
                        'is_start' => $d->format('Y-m-d') === $first->format('Y-m-d') || $n === 1,
                    ];
                }
            }
        };

        $this->bookings->blockingBookings($vehicleIds, $month, $windowEnd)
            ->with('customer:id,full_name,phone')
            ->orderBy('start_datetime')
            ->get()
            ->each(function ($b) use ($put) {
                $who = $b->customer?->full_name ?: 'Guest';
                $put($b->vehicle_id, [
                    'kind' => $b->status === 'pending' ? 'pending' : $b->status,
                    'start' => $b->start_datetime,
                    'end' => $b->end_datetime,
                    'title' => $who,
                    'sub' => $b->reference,
                    'id' => $b->id,
                    'block_id' => null,
                ]);
            });

        $this->bookings->blocksOverlapping($vehicleIds, $month, $windowEnd)
            ->orderBy('start_datetime')
            ->get()
            ->each(function ($b) use ($put) {
                $put($b->vehicle_id, [
                    'kind' => 'blocked',
                    'start' => $b->start_datetime,
                    'end' => $b->end_datetime,
                    'title' => $b->reasonLabel(),
                    'sub' => $b->note,
                    'id' => null,
                    'block_id' => $b->id,
                ]);
            });

        foreach ($entries as &$list) {
            usort($list, fn ($a, $b) => $a['start'] <=> $b['start']);
        }

        return ['days' => $days, 'entries' => $entries];
    }

    private function parseMonth(mixed $value, Carbon $today): Carbon
    {
        if (is_string($value) && preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $value)) {
            $month = Carbon::createFromFormat('Y-m-d', $value . '-01', BookingService::TIMEZONE)->startOfDay();

            // keep it within a sensible range (last year to two years ahead)
            if ($month->gte($today->copy()->startOfMonth()->subYear()) && $month->lte($today->copy()->startOfMonth()->addYears(2))) {
                return $month;
            }
        }

        return $today->copy()->startOfMonth();
    }

    private function rangeLabel(Carbon $from, Carbon $to): string
    {
        return $from->isSameDay($to)
            ? $from->format('j M')
            : $from->format('j M') . ' – ' . $to->format('j M');
    }

    private function business(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        return $user->businesses()->firstOrFail();
    }
}