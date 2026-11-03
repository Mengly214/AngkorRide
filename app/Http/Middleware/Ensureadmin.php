<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/** Admin pages: signed-in, active users whose role is "admin". Checked on the server for every request. */
class EnsureAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (! $user || $user->role !== 'admin' || ! $user->is_active) {
            if ($user && $user->role === 'admin') {
                Auth::logout();
            }

            return redirect()->route('admin.login');
        }

        return $next($request);
    }
}