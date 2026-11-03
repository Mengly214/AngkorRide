<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminLog;
use App\Models\Voucher;
use App\Support\VoucherForm;
use Illuminate\Http\Request;

/**
 * Platform-wide codes (work at every shop) are created and edited here.
 * Shop codes can be seen and paused by an admin, but not changed.
 */
class VoucherController extends Controller
{
    public function index(Request $request)                                                                                                                     
    {
        $tab = in_array($request->query('tab'), ['platform', 'shop'], true) ? $request->query('tab') : 'all';
        $q = is_string($request->query('q')) ? mb_substr(trim($request->query('q')), 0, 40) : '';

        $base = Voucher::query();
        if ($q !== '') {
            $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $q) . '%';
            $base->where(fn ($w) => $w->where('code', 'like', $like)->orWhere('description', 'like', $like)
                ->orWhereHas('business', fn ($b) => $b->where('name', 'like', $like)));
        }

        $counts = [
            'all' => (clone $base)->count(),
            'platform' => (clone $base)->whereNull('business_id')->count(),
            'shop' => (clone $base)->whereNotNull('business_id')->count(),
        ];

        $page = (clone $base)
            ->when($tab === 'platform', fn ($b) => $b->whereNull('business_id'))
            ->when($tab === 'shop', fn ($b) => $b->whereNotNull('business_id'))
            ->with('business:id,name,slug')
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $page->setCollection(VoucherForm::withUsage($page->getCollection()));

        return view('admin.vouchers.index', ['vouchers' => $page, 'counts' => $counts, 'tab' => $tab, 'q' => $q]);
    }

    public function create()
    {
        return view('admin.vouchers.form', ['voucher' => null, 'values' => VoucherForm::values(null)]);
    }

    public function store(Request $request)
    {
        $v = Voucher::create(VoucherForm::validate($request) + ['business_id' => null, 'used_count' => 0]);

        AdminLog::record($request->user(), 'voucher.create', 'Created platform voucher ' . $v->code, 'voucher', $v->id);

        return redirect()->route('admin.vouchers.index')->with('status', 'Platform voucher created. It works at every shop.');
    }

    public function edit(string $voucher)
    {
        $v = $this->platform($voucher);

        return view('admin.vouchers.form', ['voucher' => $v, 'values' => VoucherForm::values($v)]);
    }

    public function update(Request $request, string $voucher)
    {
        $v = $this->platform($voucher);
        $v->fill(VoucherForm::validate($request, $v))->save();

        AdminLog::record($request->user(), 'voucher.update', 'Edited platform voucher ' . $v->code, 'voucher', $v->id);

        return redirect()->route('admin.vouchers.index')->with('status', 'Voucher saved.');
    }

    public function toggle(Request $request, string $voucher)
    {
        $v = Voucher::query()->findOrFail($voucher);
        $v->forceFill(['is_active' => ! $v->is_active])->save();

        AdminLog::record($request->user(), 'voucher.' . ($v->is_active ? 'resume' : 'pause'), ($v->is_active ? 'Resumed ' : 'Paused ') . $v->code, 'voucher', $v->id);

        return back()->with('status', $v->code . ($v->is_active ? ' is active again.' : ' is paused.'));
    }

    public function destroy(Request $request, string $voucher)
    {
        $v = $this->platform($voucher);

        if (VoucherForm::everUsed($v)) {
            return back()->withErrors(['voucher' => $v->code . ' has been used, so it cannot be deleted. Pause it instead.']);
        }

        $v->delete();

        AdminLog::record($request->user(), 'voucher.delete', 'Deleted platform voucher ' . $v->code, 'voucher', $v->id);

        return redirect()->route('admin.vouchers.index')->with('status', 'Voucher deleted.');
    }

    private function platform(string $id): Voucher
    {
        return Voucher::query()->whereNull('business_id')->whereKey($id)->firstOrFail();
    }
}