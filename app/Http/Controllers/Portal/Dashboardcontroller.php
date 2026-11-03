<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Support\DashboardStats;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $business = $this->business($request);

        $data = DashboardStats::build($business, [
            'range' => $request->query('range'),
            'from' => $request->query('from'),
            'to' => $request->query('to'),
            'tab' => $request->query('tab'),
            'metric' => $request->query('metric'),
        ]);

        return view('portal.dashboard', $data + ['business' => $business]);
    }

    /**
     * The business owned by the logged-in portal user.
     */
    private function business(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        return $user->businesses()->firstOrFail();
    }
}