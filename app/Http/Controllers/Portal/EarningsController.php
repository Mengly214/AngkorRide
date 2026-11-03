<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Support\EarningsReport;
use Illuminate\Http\Request;

class EarningsController extends Controller
{
    public function index(Request $request)
    {
        $business = $this->business($request);
        $period = EarningsReport::period($request->query());

        $rows = EarningsReport::rentals($business, $period)
            ->with(['customer:id,full_name,phone', 'vehicle:id,brand,model,plate_number'])
            ->orderByDesc('start_datetime')
            ->paginate(15)
            ->withQueryString();

        return view('portal.earnings.index', [
            'business' => $business,
            'period' => $period,
            'report' => EarningsReport::summary($business, $period),
            'rows' => $rows,
        ]);
    }

    /** The same rentals as a spreadsheet file (opens in Excel / Google Sheets). */
    public function export(Request $request)
    {
        $business = $this->business($request);
        $period = EarningsReport::period($request->query());

        $name = 'earnings-' . match ($period['range']) {
            'month' => $period['start']->format('Y-m'),
            'year' => $period['start']->format('Y'),
            default => 'all-time',
        } . '.csv';

        $query = EarningsReport::rentals($business, $period)
            ->with(['customer:id,full_name,phone', 'vehicle:id,brand,model,plate_number'])
            ->orderBy('start_datetime');

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // so Excel reads Khmer names correctly
            fputcsv($out, ['Reference', 'Pickup', 'Return', 'Customer', 'Phone', 'Vehicle', 'Plate', 'Days', 'Voucher', 'Discount', 'Total', 'Payment', 'Status']);

            $query->chunk(200, function ($bookings) use ($out) {
                foreach ($bookings as $b) {
                    fputcsv($out, array_map([self::class, 'safeCell'], [
                        $b->reference,
                        $b->start_datetime->format('Y-m-d H:i'),
                        $b->end_datetime->format('Y-m-d H:i'),
                        $b->customer?->full_name,
                        $b->customer?->phone,
                        trim(($b->vehicle?->brand ?? '') . ' ' . ($b->vehicle?->model ?? '')),
                        $b->vehicle?->plate_number,
                        EarningsReport::chargedDays($b->start_datetime, $b->end_datetime),
                        $b->voucher_code,
                        number_format((float) $b->discount_amount, 2, '.', ''),
                        number_format((float) $b->total_price, 2, '.', ''),
                        $b->payment_status === 'paid' ? 'Paid' : 'Unpaid',
                        ucfirst($b->status),
                    ]));
                }
            });

            fclose($out);
        }, $name, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** A cell that starts with = + - @ could run as a formula in Excel; make it plain text. */
    public static function safeCell(mixed $value): mixed
    {
        return is_string($value) && $value !== '' && in_array($value[0], ['=', '+', '-', '@'], true)
            ? "'" . $value
            : $value;
    }

    private function business(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        return $user->businesses()->firstOrFail();
    }
}