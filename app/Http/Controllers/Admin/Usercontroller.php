<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    public const TABS = ['all' => null, 'customer' => 'customer', 'business_admin' => 'business_admin', 'admin' => 'admin'];

    public function index(Request $request)
    {
        $tab = array_key_exists($request->query('tab'), self::TABS) ? $request->query('tab') : 'all';
        $status = in_array($request->query('status'), ['active', 'suspended'], true) ? $request->query('status') : null;
        $q = is_string($request->query('q')) ? mb_substr(trim($request->query('q')), 0, 60) : '';

        $base = User::query();
        if ($q !== '') {
            $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $q) . '%';
            $base->where(fn ($w) => $w->where('full_name', 'like', $like)->orWhere('phone', 'like', $like)->orWhere('email', 'like', $like));
        }

        $counts = ['all' => (clone $base)->count()];
        foreach (['customer', 'business_admin', 'admin'] as $r) {
            $counts[$r] = (clone $base)->where('role', $r)->count();
        }

        $rows = (clone $base)
            ->when($tab !== 'all', fn ($b) => $b->where('role', $tab))
            ->when($status, fn ($b) => $b->where('is_active', $status === 'active'))
            ->withCount('bookings')
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.users.index', compact('rows', 'counts', 'tab', 'status', 'q'));
    }

    public function toggle(Request $request, User $user)
    {
        $admin = $request->user();
        $suspending = $user->is_active;

        if ($suspending) {
            if ($user->id === $admin->id) {
                return back()->withErrors(['user' => 'You cannot suspend your own account.']);
            }

            if ($user->role === 'admin' && User::where('role', 'admin')->where('is_active', true)->count() <= 1) {
                return back()->withErrors(['user' => 'This is the last active administrator and cannot be suspended.']);
            }

            $data = $request->validate(['reason' => 'required|string|min:5|max:500'], ['reason.required' => 'Please give a reason for the suspension.']);
        }

        $user->forceFill(['is_active' => ! $user->is_active])->save();

        if ($suspending) {
            // Sign them out everywhere right away.
            if (Schema::hasTable('sessions')) {
                DB::table('sessions')->where('user_id', $user->id)->delete();
            }
            if (method_exists($user, 'tokens')) {
                $user->tokens()->delete();
            }
        }

        AdminLog::record($admin, $suspending ? 'user.suspend' : 'user.reactivate',
            ($suspending ? 'Suspended ' : 'Reactivated ') . $user->full_name . ' (' . $user->role . ')',
            'user', $user->id, $suspending ? trim($data['reason']) : null);

        return back()->with('status', $user->full_name . ($suspending ? ' was suspended.' : ' can sign in again.'));
    }
}