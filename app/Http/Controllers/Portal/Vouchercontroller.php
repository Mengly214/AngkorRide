<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Voucher;
use App\Support\VoucherForm;
use Illuminate\Http\Request;

/** A shop's own discount codes. They only work on this shop's vehicles. */
class VoucherController extends Controller
{
    public function index(Request $request)
    {
        $business = $this->business($request);

        $vouchers = VoucherForm::withUsage($business->vouchers()->latest()->get());

        return view('portal.vouchers.index', ['business' => $business, 'vouchers' => $vouchers]);
    }

    public function create(Request $request)
    {
        return view('portal.vouchers.form', [
            'business' => $this->business($request),
            'voucher' => null,
            'values' => VoucherForm::values(null),
        ]);
    }

    public function store(Request $request)
    {
        $business = $this->business($request);

        $business->vouchers()->create(VoucherForm::validate($request) + ['used_count' => 0]);

        return redirect()->route('portal.vouchers.index')->with('status', 'Voucher created.');
    }

    public function edit(Request $request, string $voucher)
    {
        $business = $this->business($request);
        $v = $this->find($business, $voucher);

        return view('portal.vouchers.form', ['business' => $business, 'voucher' => $v, 'values' => VoucherForm::values($v)]);
    }

    public function update(Request $request, string $voucher)
    {
        $v = $this->find($this->business($request), $voucher);

        $v->fill(VoucherForm::validate($request, $v))->save();

        return redirect()->route('portal.vouchers.index')->with('status', 'Voucher saved.');
    }

    public function toggle(Request $request, string $voucher)
    {
        $v = $this->find($this->business($request), $voucher);
        $v->forceFill(['is_active' => ! $v->is_active])->save();

        return back()->with('status', $v->code . ($v->is_active ? ' is active again.' : ' is paused. Customers can no longer use it.'));
    }

    public function destroy(Request $request, string $voucher)
    {
        $v = $this->find($this->business($request), $voucher);

        if (VoucherForm::everUsed($v)) {
            return back()->withErrors(['voucher' => $v->code . ' has been used, so it cannot be deleted. Pause it instead.']);
        }

        $v->delete();

        return redirect()->route('portal.vouchers.index')->with('status', 'Voucher deleted.');
    }

    /** Only this shop's own vouchers can be opened. */
    private function find($business, string $id): Voucher
    {
        return $business->vouchers()->whereKey($id)->firstOrFail();
    }

    private function business(Request $request)
    {
        return $request->user()->businesses()->firstOrFail();
    }
}