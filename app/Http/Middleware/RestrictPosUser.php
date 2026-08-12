<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RestrictPosUser
{
    // Routes accessible to POS-role users
    private const POS_ALLOWED_PREFIXES = [
        'pos',
        'store-inventory',
        'transfer-stocks',
        'dashboard',
        'login',
        'logout',
        'settings',
        'up',
    ];

    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if ($user && $user->role === 'pos') {
            $path = $request->path();
            $allowed = collect(self::POS_ALLOWED_PREFIXES)
                ->contains(fn($prefix) => $path === $prefix || str_starts_with($path, $prefix . '/') || str_starts_with($path, $prefix . '?'));

            if (!$allowed) {
                return redirect('/pos-dashboard');
            }
        }

        return $next($request);
    }
}
