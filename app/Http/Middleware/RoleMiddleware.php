<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * Usage in routes: ->middleware('role:admin') or ->middleware('role:admin,manajer')
     */
    public function handle($request, Closure $next, $role)
    {
        if (Auth::guard($role)->check()) {
            return $next($request);
        }
        return redirect('/login-' . $role);

        if (empty($roles)) {
            return $next($request);
        }

        foreach ($roles as $role) {
            if ($user->role === $role) {
                return $next($request);
            }
        }

        // If user does not have permission, abort or redirect to dashboard with message
        if ($request->expectsJson()) {
            abort(403, 'Unauthorized.');
        }

        return redirect('/dashboard')->with('error', 'Anda tidak memiliki akses ke halaman tersebut.');
    }
}
