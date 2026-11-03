<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\CustomerReport;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $business = $this->business($request);

        $tab = in_array($request->query('tab'), ['all', 'returning', 'new'], true) ? $request->query('tab') : 'all';
        $sort = in_array($request->query('sort'), ['recent', 'spent', 'rentals'], true) ? $request->query('sort') : 'recent';
        $q = is_string($request->query('q')) ? mb_substr(trim($request->query('q')), 0, 60) : '';
        $page = max(1, (int) $request->query('page', 1));

        $all = CustomerReport::all($business);

        return view('portal.customers.index', [
            'business' => $business,
            'summary' => CustomerReport::summary($all),
            'counts' => CustomerReport::counts($all),
            'customers' => CustomerReport::page(
                $all, $q, $tab, $sort, $page, $request->url(),
                array_filter(['tab' => $tab !== 'all' ? $tab : null, 'sort' => $sort !== 'recent' ? $sort : null, 'q' => $q !== '' ? $q : null]),
            ),
            'tab' => $tab,
            'sort' => $sort,
            'search' => $q,
        ]);
    }

    /** One customer's history with this shop. */
    public function show(Request $request, string $customer)
    {
        $business = $this->business($request);

        $bookings = $business->bookings()
            ->where('customer_id', $customer)
            ->with('vehicle:id,brand,model,plate_number')
            ->orderByDesc('start_datetime')
            ->get();

        // Only people who booked with this shop can be opened.
        abort_if($bookings->isEmpty(), 404);

        $row = CustomerReport::all($business)->firstWhere('id', $customer);
        abort_unless($row, 404);

        return view('portal.customers.show', [
            'business' => $business,
            'customer' => $row,
            'bookings' => $bookings,
            'user' => User::find($customer),
        ]);
    }

    private function business(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        return $user->businesses()->firstOrFail();
    }
}